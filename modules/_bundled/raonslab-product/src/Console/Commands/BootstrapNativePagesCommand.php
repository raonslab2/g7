<?php

namespace Modules\Raonslab\Product\Console\Commands;

use Illuminate\Console\Command;
use JsonException;
use Modules\Raonslab\Product\Console\Concerns\ActsAsPageActor;
use Modules\Raonslab\Product\Services\NativePageBootstrapper;
use Throwable;

/** Imports an approved external Page payload through the official PageService. */
class BootstrapNativePagesCommand extends Command
{
    use ActsAsPageActor;

    protected $signature = 'raonslab-product:bootstrap-pages
        {payload : Absolute path to the approved native Page JSON export}
        {--actor= : Active super administrator ID or exact email for Page attribution}
        {--dry-run : Validate and report without creating missing Pages}';

    protected $description = 'Create missing RAON native Pages without updating any existing slug';

    public function handle(NativePageBootstrapper $bootstrapper): int
    {
        $path = (string) $this->argument('payload');
        if (! str_starts_with($path, '/') || ! is_file($path) || ! is_readable($path)) {
            $this->error('The payload must be an absolute path to a readable file.');

            return self::FAILURE;
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new JsonException('The payload root must be an object.');
            }

            $actor = $this->resolveActor((string) $this->option('actor'));
            if (! $actor) {
                $this->error('The actor must identify an active super administrator by ID or exact email.');

                return self::FAILURE;
            }

            $results = $this->asPageActor(
                $actor,
                'raonslab-native-page-bootstrap',
                fn (): array => $bootstrapper->bootstrap($payload, (bool) $this->option('dry-run')),
            );
            foreach ($results as $slug => $status) {
                $this->line("{$slug}: {$status}");
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Native Page bootstrap failed closed: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
