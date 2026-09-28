<?php

namespace Modules\Raonslab\Product\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Modules\Raonslab\Product\Services\NativePageBootstrapper;
use Throwable;

/** Imports an approved external Page payload through the official PageService. */
class BootstrapNativePagesCommand extends Command
{
    protected $signature = 'raonslab-product:bootstrap-pages
        {payload : Absolute path to the approved native Page JSON export}
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

            if (! $this->option('dry-run')) {
                $admin = User::where('is_super', true)->first();
                if (! $admin) {
                    $this->error('A super administrator is required for Page version attribution.');

                    return self::FAILURE;
                }
                Auth::login($admin);
            }

            $results = $bootstrapper->bootstrap($payload, (bool) $this->option('dry-run'));
            foreach ($results as $slug => $status) {
                $this->line("{$slug}: {$status}");
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Native Page bootstrap failed closed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            Auth::logout();
        }

        return self::SUCCESS;
    }
}
