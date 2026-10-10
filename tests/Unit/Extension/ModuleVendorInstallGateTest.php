<?php

namespace Tests\Unit\Extension;

use App\Contracts\Extension\CacheInterface;
use App\Contracts\Extension\ModuleInterface;
use App\Contracts\Repositories\LayoutRepositoryInterface;
use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Contracts\Repositories\ModuleRepositoryInterface;
use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\PluginRepositoryInterface;
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Contracts\Repositories\TemplateRepositoryInterface;
use App\Extension\ExtensionManager;
use App\Extension\ModuleManager;
use App\Extension\Vendor\EnvironmentDetector;
use App\Extension\Vendor\Exceptions\VendorInstallException;
use App\Extension\Vendor\VendorBundleInstaller;
use App\Extension\Vendor\VendorBundler;
use App\Extension\Vendor\VendorIntegrityChecker;
use App\Extension\Vendor\VendorMode;
use App\Extension\Vendor\VendorResolver;
use App\Services\LayoutExtensionService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Mockery;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * Calls native installModule and VendorResolver in an unbooted, temporary application. No dotenv,
 * service providers, SQL driver or real Composer process is loaded. Repository/DDL boundaries are
 * mocked; three isolated fixtures also load real entry/install code and dependency classes/functions.
 * Copy, mode selection, bundle integrity, extraction and rollback remain native.
 */
class ModuleVendorInstallGateTest extends TestCase
{
    private const IDENTIFIER = 'test-vendorgate';

    private string $root;

    private Application $app;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private Filesystem $files;

    private $extensionManager;

    private $moduleRepository;

    private $module;

    private $manager;

    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->root = sys_get_temp_dir().'/g7-module-vendor-gate-'.bin2hex(random_bytes(8));
        $this->files = new Filesystem;
        $this->files->makeDirectory($this->root, 0700, true);
        $this->app = new Application($this->root);
        $this->app->instance('env', 'production'); // in-memory branch selection; never boots a real app
        $this->app->instance('config', new Repository(['app' => ['version' => '7.0.11', 'install' => ['default_vendor_mode' => 'auto']], 'process' => ['composer_binary' => null]]));
        $this->app->instance('files', $this->files);
        $this->app->instance('translator', new Translator(new ArrayLoader, 'en'));
        $this->app->instance('log', new Logger('vendor-gate', [new NullHandler]));
        $this->db = Mockery::mock(); // any unplanned DB call fails; no connection factory exists
        $this->app->instance('db', $this->db);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        $this->extensionManager = Mockery::mock(ExtensionManager::class)->makePartial();
        $this->extensionManager->shouldReceive('runComposerInstall')->never();
        $this->moduleRepository = Mockery::mock(ModuleRepositoryInterface::class);
        $this->moduleRepository->shouldReceive('findByIdentifier')->with(self::IDENTIFIER)->andReturnNull();
        $this->module = Mockery::mock(ModuleInterface::class);
        foreach (['getIdentifier' => self::IDENTIFIER, 'getVendor' => 'test', 'getVersion' => '1.0.0', 'getName' => ['en' => 'Synthetic gate fixture'], 'getDescription' => ['en' => 'No database or network'], 'getRequiredCoreVersion' => null, 'getDependencies' => [], 'getPermissions' => [], 'seoVariables' => [], 'getGithubUrl' => null, 'getMetadata' => [], 'getConfig' => []] as $method => $value) {
            $this->module->shouldReceive($method)->andReturn($value);
        }
        $this->module->shouldReceive('clearLifecycleFailureReason')->andReturnNull();
        $this->manager = Mockery::mock(ModuleManager::class, [
            $this->extensionManager,
            $this->moduleRepository,
            Mockery::mock(PermissionRepositoryInterface::class),
            Mockery::mock(RoleRepositoryInterface::class),
            Mockery::mock(MenuRepositoryInterface::class),
            Mockery::mock(TemplateRepositoryInterface::class),
            Mockery::mock(PluginRepositoryInterface::class),
            Mockery::mock(LayoutRepositoryInterface::class),
            Mockery::mock(LayoutExtensionService::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();
        $this->manager->shouldReceive('runModuleSeeders')->never();
        $this->manager->shouldReceive('initializeModuleSettings')->never();
    }

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            $this->files->deleteDirectory($this->root);
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->previousFacadeApplication);
            Container::setInstance($this->previousContainer);
            parent::tearDown();
        }
    }

    private function source(string $kind = '_bundled', bool $dependencies = true): string
    {
        $source = $this->root.'/modules/'.$kind.'/'.self::IDENTIFIER;
        $this->files->makeDirectory($source, 0700, true);
        file_put_contents($source.'/module.json', json_encode(['identifier' => self::IDENTIFIER, 'version' => '1.0.0', 'vendor' => 'test']));
        file_put_contents($source.'/module.php', '<?php // Synthetic entry; lifecycle is an injected test boundary.');
        file_put_contents($source.'/composer.json', json_encode(['name' => 'test/vendor-gate', 'require' => $dependencies ? ['php' => '^8.2', 'fixture/dependency' => '^1.0'] : ['php' => '^8.2', 'ext-json' => '*']]));

        return $source;
    }

    private function active(): string
    {
        return $this->root.'/modules/'.self::IDENTIFIER;
    }

    private function resolver(bool $composerAvailable): void
    {
        $detector = new class($composerAvailable) extends EnvironmentDetector
        {
            public function __construct(private bool $available) {}

            public function canExecuteComposer(?string $hint = null): bool
            {
                return $this->available;
            }
        };
        $checker = new VendorIntegrityChecker;
        $this->app->instance(VendorResolver::class, new VendorResolver($detector, $checker, new VendorBundleInstaller($checker)));
    }

    private function bundle(string $source, bool $corrupt = false): void
    {
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($source.'/vendor-bundle.zip', \ZipArchive::CREATE));
        $zip->addFromString('vendor/autoload.php', '<?php // Synthetic dependency package');
        $zip->addFromString('vendor/fixture/dependency/marker.txt', 'isolated native bundle');
        $zip->close();
        file_put_contents($source.'/vendor-bundle.json', json_encode([
            'schema_version' => VendorBundler::SCHEMA_VERSION,
            'zip_sha256' => $corrupt ? str_repeat('0', 64) : hash_file('sha256', $source.'/vendor-bundle.zip'),
            'composer_json_sha256' => hash_file('sha256', $source.'/composer.json'),
            'package_count' => 1,
        ]));
    }

    public static function invalidBundles(): array
    {
        return [
            'ordinary bundled missing archive' => ['_bundled', false, false, 'bundle_zip_missing'],
            'ordinary bundled corrupt checksum' => ['_bundled', true, false, 'bundle_integrity_failed'],
            'pending missing archive' => ['_pending', false, false, 'bundle_zip_missing'],
            'pending corrupt checksum before force replacement' => ['_pending', true, true, 'bundle_integrity_failed'],
        ];
    }

    #[DataProvider('invalidBundles')]
    public function test_dependency_failure_stops_before_lifecycle_and_preserves_source(string $kind, bool $corrupt, bool $force, string $key): void
    {
        $source = $this->source($kind);
        if ($corrupt) {
            $this->bundle($source, true);
        }
        if ($force) {
            $this->files->makeDirectory($this->active(), 0700, true);
            file_put_contents($this->active().'/existing.txt', 'existing active installation');
        }
        $sourceHash = hash_file('sha256', $source.'/composer.json');
        $this->resolver(true); // explicit Bundled must not choose Composer even when available
        $this->extensionManager->shouldReceive('runComposerInstallAt')->never();
        $this->manager->shouldReceive('getModule')->never();
        $this->manager->shouldReceive('runMigrations')->never();
        $this->module->shouldReceive('install')->never();
        $this->moduleRepository->shouldReceive('updateOrCreate')->never();
        $progress = [];
        try {
            $this->manager->installModule(self::IDENTIFIER, function ($step) use (&$progress) {
                $progress[] = $step;
            }, VendorMode::Bundled, $force);
            $this->fail('Vendor failure must propagate; installation must not report success.');
        } catch (VendorInstallException $e) {
            $this->assertSame($key, $e->getErrorKey());
        }
        $this->assertSame($sourceHash, hash_file('sha256', $source.'/composer.json'));
        $this->assertFileDoesNotExist($source.'/vendor/autoload.php');
        if ($force) {
            $this->assertSame('existing active installation', file_get_contents($this->active().'/existing.txt'));
        } else {
            $this->assertDirectoryDoesNotExist($this->active());
        }
        if ($kind === '_pending') {
            $this->assertNotContains('copy', $progress, 'Pending failure must occur before any publication/copy.');
        }
    }

    public static function resolvedModes(): array
    {
        return [
            'explicit bundled despite available Composer' => ['_bundled', VendorMode::Bundled, true, VendorMode::Bundled],
            'auto falls back to bundled without Composer' => ['_bundled', VendorMode::Auto, false, VendorMode::Bundled],
            'explicit Composer callback' => ['_bundled', VendorMode::Composer, true, VendorMode::Composer],
            'auto selects Composer' => ['_bundled', VendorMode::Auto, true, VendorMode::Composer],
            'pending bundle copied once, not resolved again' => ['_pending', VendorMode::Bundled, true, VendorMode::Bundled],
        ];
    }

    #[DataProvider('resolvedModes')]
    public function test_vendor_is_ready_before_lifecycle_and_resolved_mode_reaches_native_registration(string $kind, VendorMode $requested, bool $composerAvailable, VendorMode $expected): void
    {
        $source = $this->source($kind);
        $this->bundle($source);
        $this->resolver($composerAvailable);
        if ($expected === VendorMode::Composer) {
            $this->extensionManager->shouldReceive('runComposerInstallAt')->once()->with($this->active(), true)->andReturnUsing(function ($path) {
                $this->files->makeDirectory($path.'/vendor', 0700, true);
                file_put_contents($path.'/vendor/autoload.php', '<?php // Composer boundary stub');

                return true;
            });
        } else {
            $this->extensionManager->shouldReceive('runComposerInstallAt')->never();
        }
        $phases = [];
        $this->manager->shouldReceive('getModule')->once()->with(self::IDENTIFIER)->andReturn($this->module);
        $this->module->shouldReceive('install')->once()->andReturnUsing(function () use (&$phases) {
            $this->assertFileExists($this->active().'/vendor/autoload.php');
            $phases[] = 'install';

            return true;
        });
        $this->manager->shouldReceive('runMigrations')->once()->with($this->module)->andReturnUsing(function () use (&$phases) {
            $phases[] = 'migration';
        });
        $this->db->shouldReceive('beginTransaction')->once()->andReturnUsing(function () use (&$phases) {
            $phases[] = 'db';
        });
        $this->db->shouldReceive('rollBack')->once();
        $captured = null;
        $stop = new \RuntimeException('Intentional boundary: native metadata captured; no database write.');
        $this->moduleRepository->shouldReceive('updateOrCreate')->once()->andReturnUsing(function ($identity, $attributes) use (&$captured, $stop) {
            $this->assertSame(['identifier' => self::IDENTIFIER], $identity);
            $captured = $attributes;
            throw $stop;
        });
        $this->app->instance('auth', Mockery::mock()->shouldReceive('id')->twice()->andReturnNull()->getMock());
        try {
            $this->manager->installModule(self::IDENTIFIER, vendorMode: $requested);
            $this->fail('The explicit no-DB registration boundary must terminate this fixture.');
        } catch (\RuntimeException $e) {
            $this->assertSame($stop, $e);
        }
        $this->assertSame(['install', 'migration', 'db'], $phases);
        $this->assertSame($expected->value, $captured['vendor_mode']);
        $this->assertDirectoryDoesNotExist($this->active(), 'Native rollback removes this fixture-created active directory.');
        $this->assertFileExists($source.'/module.json');
        if ($kind === '_pending') {
            $this->assertFileExists($source.'/vendor/autoload.php');
        } else {
            $this->assertFileDoesNotExist($source.'/vendor/autoload.php', 'Ordinary bundled source must remain untouched.');
        }
    }

    public function test_composer_failure_propagates_before_module_install(): void
    {
        $this->source();
        $this->resolver(true);
        $this->extensionManager->shouldReceive('runComposerInstallAt')->once()->with($this->active(), true)->andReturnFalse();
        $this->manager->shouldReceive('getModule')->never();
        $this->module->shouldReceive('install')->never();
        $this->manager->shouldReceive('runMigrations')->never();
        $this->moduleRepository->shouldReceive('updateOrCreate')->never();
        try {
            $this->manager->installModule(self::IDENTIFIER, vendorMode: VendorMode::Composer);
            $this->fail('Failed Composer execution must not be reduced to a late warning.');
        } catch (VendorInstallException $e) {
            $this->assertSame('composer_execution_failed', $e->getErrorKey());
        }
        $this->assertDirectoryDoesNotExist($this->active());
    }

    public static function entryDependencyModes(): array
    {
        return [
            'ordinary bundle actual entry' => ['_bundled', VendorMode::Bundled],
            'pending bundle actual entry' => ['_pending', VendorMode::Bundled],
            'Composer callback actual entry' => ['_bundled', VendorMode::Composer],
        ];
    }

    /** Real module entry/install and package class/function loading, isolated from PHP class state. */
    #[DataProvider('entryDependencyModes')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_prepared_vendor_autoloader_resolves_dependency_at_native_entry_and_install(string $kind, VendorMode $mode): void
    {
        $source = $this->source($kind);
        $entry = <<<'PHP'
<?php
namespace Modules\Test\Vendorgate;
use GateFixtureDependency\Value;
use function GateFixtureDependency\marker;
file_put_contents(\base_path('fixture-phases.log'), 'entry:'.Value::read().':'.marker()."\n", FILE_APPEND);
class Module extends \App\Extension\AbstractModule
{
    public function install(): bool
    {
        file_put_contents(\base_path('fixture-phases.log'), 'install:'.Value::read().':'.marker()."\n", FILE_APPEND);
        throw new \RuntimeException('Fixture native install reached dependency');
    }
}
PHP;
        file_put_contents($source.'/module.php', $entry);
        $vendorFiles = [
            'vendor/autoload.php' => <<<'PHP'
<?php
$loader = new \Composer\Autoload\ClassLoader(__DIR__);
$loader->addPsr4('GateFixtureDependency\\', __DIR__.'/fixture/dependency/src');
$loader->register();
require_once __DIR__.'/fixture/dependency/helpers.php';
file_put_contents(\base_path('fixture-vendor-loads.log'), "loaded\n", FILE_APPEND);
return $loader;
PHP,
            'vendor/fixture/dependency/src/Value.php' => '<?php namespace GateFixtureDependency; final class Value { public static function read(): string { return "class-ready"; } }',
            'vendor/fixture/dependency/helpers.php' => '<?php namespace GateFixtureDependency; function marker(): string { return "function-ready"; }',
        ];
        if ($mode === VendorMode::Composer) {
            $this->extensionManager->shouldReceive('runComposerInstallAt')->once()->with($this->active(), true)->andReturnUsing(function ($path) use ($vendorFiles) {
                foreach ($vendorFiles as $relative => $contents) {
                    $this->files->ensureDirectoryExists(dirname($path.'/'.$relative), 0700);
                    file_put_contents($path.'/'.$relative, $contents);
                }

                return true;
            });
        } else {
            $this->bundle($source);
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($source.'/vendor-bundle.zip'));
            foreach ($vendorFiles as $relative => $contents) {
                $zip->addFromString($relative, $contents);
            }
            $zip->close();
            $manifest = json_decode(file_get_contents($source.'/vendor-bundle.json'), true);
            $manifest['zip_sha256'] = hash_file('sha256', $source.'/vendor-bundle.zip');
            file_put_contents($source.'/vendor-bundle.json', json_encode($manifest));
            $this->extensionManager->shouldReceive('runComposerInstallAt')->never();
        }
        $this->resolver(true);
        // Only hook/cache/DB boundaries are isolated; getModule/reloadModule and entry stay native.
        $this->manager->shouldReceive('registerModuleHookListeners')->once()->andReturnNull();
        $cache = Mockery::mock(CacheInterface::class);
        $cache->shouldReceive('supportsTags')->once()->andReturnFalse();
        $cache->shouldReceive('forget')->once();
        $this->app->instance(CacheInterface::class, $cache);
        $this->manager->shouldReceive('runMigrations')->never();
        $this->moduleRepository->shouldReceive('updateOrCreate')->never();
        $this->assertFalse(class_exists('GateFixtureDependency\\Value', false));
        $this->assertFalse(function_exists('GateFixtureDependency\\marker'));
        try {
            $this->manager->installModule(self::IDENTIFIER, vendorMode: $mode);
            $this->fail('The real fixture install must terminate before migrations/DB.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Fixture native install reached dependency', $exception->getMessage());
        }
        $this->assertSame("entry:class-ready:function-ready\ninstall:class-ready:function-ready\n", file_get_contents($this->root.'/fixture-phases.log'));
        $this->assertSame("loaded\n", file_get_contents($this->root.'/fixture-vendor-loads.log'), 'One active autoloader load; test never loads the package manually.');
        $this->assertSame($this->active().'/vendor/fixture/dependency/src/Value.php', (new \ReflectionClass('GateFixtureDependency\\Value'))->getFileName());
        $this->assertSame($this->active().'/vendor/fixture/dependency/helpers.php', (new \ReflectionFunction('GateFixtureDependency\\marker'))->getFileName());
        $this->assertSame($this->active().'/module.php', (new \ReflectionObject($this->manager->getModule(self::IDENTIFIER)))->getFileName());
        $this->assertDirectoryDoesNotExist($this->active(), 'The original install Throwable rollback also covers this real fixture.');
        $this->assertSame($entry, file_get_contents($source.'/module.php'));
    }

    public static function bypasses(): array
    {
        return ['no external dependencies' => [false, 'production'], 'existing testing policy' => [true, 'testing']];
    }

    #[DataProvider('bypasses')]
    public function test_no_dependency_and_testing_bypasses_keep_existing_policy(bool $dependencies, string $environment): void
    {
        $this->source(dependencies: $dependencies); // deliberately no vendor bundle
        $this->app->instance('env', $environment);
        $this->extensionManager->shouldReceive('runComposerInstallAt')->never();
        $this->manager->shouldReceive('getModule')->once()->with(self::IDENTIFIER)->andReturn($this->module);
        $this->module->shouldReceive('install')->once()->andReturnFalse();
        $this->module->shouldReceive('getLifecycleFailureReason')->once()->andReturn('Fixture lifecycle stopped.');
        $this->manager->shouldReceive('runMigrations')->never();
        $this->moduleRepository->shouldReceive('updateOrCreate')->never();
        $reason = null;
        $this->assertFalse($this->manager->installModule(self::IDENTIFIER, vendorMode: VendorMode::Bundled, failureReason: $reason));
        $this->assertSame('Fixture lifecycle stopped.', $reason);
        $this->assertDirectoryExists($this->active());
        $this->assertFileDoesNotExist($this->active().'/vendor/autoload.php');
    }
}
