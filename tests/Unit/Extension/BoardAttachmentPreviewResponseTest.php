<?php

namespace Tests\Unit\Extension;

use App\Contracts\Extension\StorageInterface;
use App\Models\User;
use Composer\Autoload\ClassLoader;
use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\RequestGuard;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Mockery;
use Modules\Sirsoft\Board\Http\Controllers\User\AttachmentController;
use Modules\Sirsoft\Board\Models\Attachment;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use Modules\Sirsoft\Board\Repositories\Contracts\AttachmentRepositoryInterface;
use Modules\Sirsoft\Board\Repositories\Contracts\BoardRepositoryInterface;
use Modules\Sirsoft\Board\Services\AttachmentService;
use Modules\Sirsoft\Board\Services\BoardService;
use Modules\Sirsoft\Board\Support\SecretContentGate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Real bundled controller/services/ResponseHelper with repository/storage boundary fixtures.
 * No Laravel bootstrap, environment file, SQL connection, installed module or service process.
 * This narrow unit scope complements, and does not replace, native ModuleTestCase/live HTTP gates.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class BoardAttachmentPreviewResponseTest extends TestCase
{
    private Application $app;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private ClassLoader $moduleLoader;

    private AttachmentRepositoryInterface $attachments;

    private BoardRepositoryInterface $boards;

    private StorageInterface $storage;

    private AttachmentController $controller;

    private Request $request;

    private ?User $actor = null;

    private ?string $imageFile = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->app = new Application;
        Container::setInstance($this->app);
        $this->app->instance('env', 'testing');
        $this->app->instance('config', new ConfigRepository(['app' => ['locale' => 'en', 'debug' => false]]));
        $source = dirname(__DIR__, 3).'/modules/_bundled/sirsoft-board/src';
        $this->moduleLoader = new ClassLoader;
        $this->moduleLoader->addPsr4('Modules\\Sirsoft\\Board\\', $source);
        $this->moduleLoader->register(true);
        self::assertSame(realpath($source.'/Http/Controllers/User/AttachmentController.php'), (new \ReflectionClass(AttachmentController::class))->getFileName());
        self::assertSame(realpath($source.'/Services/AttachmentService.php'), (new \ReflectionClass(AttachmentService::class))->getFileName());

        $loader = new FileLoader(new Filesystem, []);
        $loader->addNamespace('sirsoft-board', $source.'/lang');
        $this->app->instance('translator', new Translator($loader, 'en'));
        $this->request = Request::create('/api/modules/sirsoft-board/boards/unit-preview/attachment/synthetic/preview');
        $this->app->instance('request', $this->request);
        $url = new UrlGenerator(new RouteCollection, $this->request);
        $url->setKeyResolver(static fn () => 'unit-only-synthetic-signature-key');
        $this->app->instance('url', $url);
        $this->app->instance(ResponseFactoryContract::class, new ResponseFactory(Mockery::mock(ViewFactory::class), new Redirector($url)));
        $this->app->instance('auth', new RequestGuard(fn () => $this->actor, $this->request));
        $this->app->instance(GateContract::class, new Gate($this->app, fn () => $this->actor));
        $this->app->instance(SecretContentGate::class, new SecretContentGate);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        (new FoundationServiceProvider($this->app))->registerRequestSignatureValidation();

        $this->attachments = Mockery::mock(AttachmentRepositoryInterface::class);
        $this->boards = Mockery::mock(BoardRepositoryInterface::class);
        $this->storage = Mockery::mock(StorageInterface::class);
        $board = new Board;
        $board->setRawAttributes(['id' => 1, 'slug' => 'unit-preview']);
        $this->boards->shouldReceive('findBySlug')->once()->with('unit-preview')->andReturn($board);
        $arguments = [];
        foreach ((new \ReflectionClass(BoardService::class))->getConstructor()->getParameters() as $parameter) {
            $arguments[] = match ($parameter->getName()) {
                'boardRepository' => $this->boards,
                'attachmentRepository' => $this->attachments,
                'storage' => $this->storage,
                default => Mockery::mock($parameter->getType()->getName()),
            };
        }
        $this->controller = new AttachmentController(
            new AttachmentService($this->attachments, $this->boards, $this->storage),
            new BoardService(...$arguments)
        );
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            if ($this->imageFile !== null) {
                unlink($this->imageFile);
            }
            $this->moduleLoader->unregister();
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->previousFacadeApplication);
            Container::setInstance($this->previousContainer);
            parent::tearDown();
        }
    }

    public static function locales(): array
    {
        return ['Korean' => ['ko', '이미지 파일만 미리보기가 가능합니다.'], 'English' => ['en', 'Only image files can be previewed.']];
    }

    #[DataProvider('locales')]
    public function test_nonimage_preview_is_translated_400_without_file_or_gate_access(string $locale, string $message): void
    {
        $this->app['config']->set('app.locale', $locale);
        $this->app['translator']->setLocale($locale);
        $attachment = $this->attachment('text/plain');
        self::assertFalse($attachment->is_image);
        $this->attachments->shouldReceive('findByHash')->once()->with('unit-preview', 'synthetic')->andReturn($attachment);
        $this->attachments->shouldNotReceive('findById');
        $this->storage->shouldNotReceive('exists');
        $this->storage->shouldNotReceive('response');
        $response = $this->controller->preview($this->request, 'unit-preview', 'synthetic');
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame(['success' => false, 'message' => $message], $response->getData(true));
        self::assertStringNotContainsString($attachment->path, $response->getContent());
        self::assertStringNotContainsString($attachment->original_filename, $response->getContent());
    }

    public function test_missing_hash_remains_404_without_file_access(): void
    {
        $this->attachments->shouldReceive('findByHash')->once()->with('unit-preview', 'synthetic')->andReturnNull();
        $this->attachments->shouldNotReceive('findById');
        $this->storage->shouldNotReceive('exists');
        $response = $this->controller->preview($this->request, 'unit-preview', 'synthetic');
        self::assertSame(404, $response->getStatusCode());
        self::assertSame(['success' => false, 'message' => __('sirsoft-board::messages.attachment.not_found')], $response->getData(true));
    }

    public function test_unsigned_image_with_unresolved_parent_remains_403_before_storage_access(): void
    {
        $attachment = $this->imageLookup();
        $this->attachments->shouldReceive('isPostDeleted')->once()->with('unit-preview', 2)->andReturnFalse();
        $this->attachments->shouldReceive('findPostForGate')->once()->with('unit-preview', 2)->andReturnNull();
        $this->assertImageDeniedBeforeStorage($attachment);
    }

    public function test_deleted_image_remains_403_for_actor_without_native_gate_permissions(): void
    {
        $this->actor = new User;
        $this->actor->setRawAttributes(['id' => 3]);
        $attachment = $this->imageLookup();
        $this->attachments->shouldReceive('isPostDeleted')->once()->with('unit-preview', 2)->andReturnTrue();
        $this->attachments->shouldNotReceive('findPostForGate');
        // Actual native Gate has no abilities granted; the fixture needs no role/permission SQL.
        $this->assertImageDeniedBeforeStorage($attachment);
    }

    public function test_secret_image_owner_still_receives_actual_file_bytes_through_native_service(): void
    {
        $this->actor = new User;
        $this->actor->setRawAttributes(['id' => 3]);
        $attachment = $this->imageLookup();
        $post = new Post;
        $post->setRawAttributes(['id' => 2, 'is_secret' => true, 'user_id' => 3]);
        $this->attachments->shouldReceive('isPostDeleted')->once()->with('unit-preview', 2)->andReturnFalse();
        $this->attachments->shouldReceive('findPostForGate')->once()->with('unit-preview', 2)->andReturn($post);
        $this->imageFile = tempnam(sys_get_temp_dir(), 'board-preview-unit-');
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6iVIAAAAASUVORK5CYII=');
        file_put_contents($this->imageFile, $bytes);
        $attachment->path = basename($this->imageFile);
        $this->storage->shouldReceive('exists')->once()->with('attachments', $attachment->path)->andReturnTrue();
        $this->storage->shouldReceive('getBasePath')->once()->with('attachments')->andReturn(dirname($this->imageFile));
        $response = $this->controller->preview($this->request, 'unit-preview', 'synthetic');
        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->headers->get('Content-Type'));
        self::assertSame($bytes, file_get_contents($response->getFile()->getPathname()));
        self::assertNotNull($response->headers->get('ETag'));
    }

    private function attachment(string $mime): Attachment
    {
        $attachment = new Attachment;
        $attachment->setRawAttributes([
            'id' => 1, 'post_id' => 2, 'hash' => 'synthetic', 'mime_type' => $mime,
            'path' => 'private-fixture/synthetic-file', 'original_filename' => 'synthetic-private-name.txt',
        ]);

        return $attachment;
    }

    private function imageLookup(): Attachment
    {
        $attachment = $this->attachment('image/png');
        self::assertTrue($attachment->is_image);
        $this->attachments->shouldReceive('findByHash')->once()->with('unit-preview', 'synthetic')->andReturn($attachment);
        $this->attachments->shouldReceive('findById')->once()->with('unit-preview', 1)->andReturn($attachment);
        self::assertFalse($this->request->hasValidSignature(absolute: false));

        return $attachment;
    }

    private function assertImageDeniedBeforeStorage(Attachment $attachment): void
    {
        $this->storage->shouldNotReceive('exists');
        $this->storage->shouldNotReceive('getBasePath');
        $this->storage->shouldNotReceive('response');
        $response = $this->controller->preview($this->request, 'unit-preview', 'synthetic');
        self::assertSame(403, $response->getStatusCode());
        self::assertFalse($response->getData(true)['success']);
        self::assertSame(['success', 'message'], array_keys($response->getData(true)));
        self::assertStringNotContainsString($attachment->path, $response->getContent());
        self::assertStringNotContainsString($attachment->original_filename, $response->getContent());
    }
}
