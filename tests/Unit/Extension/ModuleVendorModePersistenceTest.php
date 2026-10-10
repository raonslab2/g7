<?php

namespace Tests\Unit\Extension;

use App\Extension\Vendor\VendorMode;
use App\Repositories\ModuleRepository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Real repository/model/migration with only a process-local SQLite connection; no application boot. */
class ModuleVendorModePersistenceTest extends TestCase
{
    private Capsule $database;

    private ModuleRepository $repository;

    private Container $previousContainer;

    private $previousFacadeApplication;

    private $previousResolver;

    private $previousDispatcher;

    private bool $previousDiscardProtection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $this->previousResolver = Model::getConnectionResolver();
        $this->previousDispatcher = Model::getEventDispatcher();
        $this->previousDiscardProtection = Model::preventsSilentlyDiscardingAttributes();
        $container = new Container;
        Container::setInstance($container);
        $this->database = new Capsule($container);
        $this->database->addConnection([
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $container->instance('db', $this->database->getDatabaseManager());
        $container->instance('db.schema', $this->database->getConnection()->getSchemaBuilder());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
        Model::unsetEventDispatcher();
        Model::preventSilentlyDiscardingAttributes(false); // Native production default; keep guarding enabled.
        $this->database->bootEloquent();
        self::assertFalse(Model::isUnguarded());
        self::assertSame(':memory:', $this->database->getConnection()->getDatabaseName());

        $schema = $this->database->getConnection()->getSchemaBuilder();
        $schema->create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('identifier')->unique();
            $table->string('vendor');
            $table->json('name');
            $table->string('version');
            $table->string('status')->default('inactive');
            $table->json('metadata')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        $schema->create('plugins', function (Blueprint $table): void {
            $table->id();
            $table->string('status')->default('inactive');
        });
        $migration = require dirname(__DIR__, 3).'/database/migrations/2026_04_14_000001_add_vendor_mode_to_modules_and_plugins_tables.php';
        $migration->up();
        $this->repository = new ModuleRepository;
    }

    protected function tearDown(): void
    {
        $this->database->getDatabaseManager()->disconnect();
        if ($this->previousResolver !== null) {
            Model::setConnectionResolver($this->previousResolver);
        } else {
            Model::unsetConnectionResolver();
        }
        if ($this->previousDispatcher !== null) {
            Model::setEventDispatcher($this->previousDispatcher);
        } else {
            Model::unsetEventDispatcher();
        }
        Model::preventSilentlyDiscardingAttributes($this->previousDiscardProtection);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public static function modes(): array
    {
        return [
            'bundled' => [VendorMode::Bundled],
            'composer' => [VendorMode::Composer],
            'explicit auto' => [VendorMode::Auto],
        ];
    }

    #[DataProvider('modes')]
    public function test_create_persists_native_mode_as_string_and_preserves_json_metadata(VendorMode $mode): void
    {
        $created = $this->repository->create($this->attributes('test-create') + ['vendor_mode' => $mode->value]);
        $stored = $this->repository->findByIdentifier('test-create');
        self::assertNotNull($stored);
        self::assertSame($created->id, $stored->id);
        self::assertSame($mode->value, $stored->vendor_mode);
        self::assertIsString($stored->vendor_mode);
        self::assertSame($mode, VendorMode::tryFrom((string) $stored->vendor_mode));
        self::assertSame($mode->value, $this->database->getConnection()->table('modules')->value('vendor_mode'));
        self::assertSame($this->attributes('test-create')['metadata'], $stored->metadata);
        self::assertSame($this->attributes('test-create')['config'], $stored->config);
        self::assertSame(['ko' => '합성 모듈', 'en' => 'Synthetic module'], $stored->name);
    }

    #[DataProvider('modes')]
    public function test_update_roundtrip_changes_mode_without_replacing_identity_or_metadata(VendorMode $mode): void
    {
        $initialMode = $mode === VendorMode::Auto ? VendorMode::Bundled : VendorMode::Auto;
        $created = $this->repository->create($this->attributes('test-update') + ['vendor_mode' => $initialMode->value]);
        self::assertSame($initialMode->value, $created->fresh()->vendor_mode);
        self::assertTrue($this->repository->update($created, ['vendor_mode' => $mode->value]));
        $stored = $this->repository->findByIdentifier('test-update');
        self::assertSame($created->id, $stored->id);
        self::assertSame($mode->value, $stored->vendor_mode);
        self::assertSame($this->attributes('test-update')['metadata'], $stored->metadata);
        self::assertSame($this->attributes('test-update')['config'], $stored->config);
        self::assertSame(1, $this->database->getConnection()->table('modules')->count());
    }

    public function test_install_style_update_or_create_persists_bundled_then_composer_on_same_row(): void
    {
        $created = $this->repository->updateOrCreate(['identifier' => 'test-install'], $this->attributes('test-install') + ['vendor_mode' => VendorMode::Bundled->value]);
        self::assertSame('bundled', $created->fresh()->vendor_mode);
        $updated = $this->repository->updateOrCreate(['identifier' => 'test-install'], ['version' => '1.0.1', 'vendor_mode' => VendorMode::Composer->value]);
        self::assertSame($created->id, $updated->id);
        self::assertSame('composer', $updated->fresh()->vendor_mode);
        self::assertSame('1.0.1', $updated->fresh()->version);
        self::assertSame($this->attributes('test-install')['metadata'], $updated->fresh()->metadata);
        self::assertSame(1, $this->database->getConnection()->table('modules')->count());
    }

    public function test_omitted_mode_uses_native_migration_auto_default_and_string_fallback(): void
    {
        $created = $this->repository->create($this->attributes('test-default'));
        self::assertSame('auto', $created->fresh()->vendor_mode);
        self::assertSame(VendorMode::Auto, VendorMode::fromStringOrAuto($created->fresh()->vendor_mode));
        self::assertSame(VendorMode::Auto, VendorMode::fromStringOrAuto(null));
        self::assertSame(VendorMode::Auto, VendorMode::fromStringOrAuto(''));
        self::assertSame(VendorMode::Auto, VendorMode::fromStringOrAuto('unknown'));
        self::assertTrue($this->repository->update($created, ['version' => '1.0.2']));
        self::assertSame('auto', $created->fresh()->vendor_mode);
    }

    public function test_install_style_update_with_omitted_mode_preserves_stored_bundled_selection(): void
    {
        $created = $this->repository->updateOrCreate(['identifier' => 'test-inherit'], $this->attributes('test-inherit') + ['vendor_mode' => 'bundled']);
        self::assertSame('bundled', $created->fresh()->vendor_mode);
        $updated = $this->repository->updateOrCreate(['identifier' => 'test-inherit'], ['version' => '1.0.3']);
        self::assertSame($created->id, $updated->id);
        self::assertSame('bundled', $updated->fresh()->vendor_mode);
        self::assertSame(1, $this->database->getConnection()->table('modules')->count());
    }

    public function test_ordinary_mass_assignment_still_protects_primary_key_and_unfillable_attributes(): void
    {
        $created = $this->repository->create($this->attributes('test-guard') + ['id' => 900, 'is_active' => true, 'vendor_mode' => 'bundled']);
        self::assertNotSame(900, $created->id);
        self::assertFalse($created->fresh()->is_active);
        self::assertTrue($this->repository->update($created, ['id' => 901, 'is_active' => true, 'vendor_mode' => 'composer']));
        $stored = $this->repository->findByIdentifier('test-guard');
        self::assertSame($created->id, $stored->id);
        self::assertFalse($stored->is_active);
        self::assertSame('composer', $stored->vendor_mode);
        self::assertFalse(Model::isUnguarded());
    }

    private function attributes(string $identifier): array
    {
        return [
            'identifier' => $identifier, 'vendor' => 'test', 'version' => '1.0.0',
            'name' => ['ko' => '합성 모듈', 'en' => 'Synthetic module'], 'status' => 'inactive',
            'metadata' => ['native' => ['dependencies' => ['php' => '>=8.2'], 'features' => ['safe', 'synthetic']], 'enabled' => true],
            'config' => ['ui' => ['locale' => 'ko'], 'capacity' => 3],
        ];
    }
}
