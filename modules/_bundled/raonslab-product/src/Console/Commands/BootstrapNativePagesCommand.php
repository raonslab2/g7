<?php

namespace Modules\Raonslab\Product\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use JsonException;
use Modules\Raonslab\Product\Auth\NativePageActorGuard;
use Modules\Raonslab\Product\Services\NativePageBootstrapper;
use Throwable;

/** Imports an approved external Page payload through the official PageService. */
class BootstrapNativePagesCommand extends Command
{
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

            $auth = Auth::getFacadeRoot();
            $previousGuard = $auth->getDefaultDriver();
            $guardName = 'raonslab-native-page-bootstrap';
            config(["auth.guards.{$guardName}" => ['driver' => $guardName]]);
            $auth->extend($guardName, fn () => new NativePageActorGuard($actor));
            $auth->shouldUse($guardName);

            $results = $bootstrapper->bootstrap($payload, (bool) $this->option('dry-run'));
            foreach ($results as $slug => $status) {
                $this->line("{$slug}: {$status}");
            }
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Native Page bootstrap failed closed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            if (isset($auth, $previousGuard)) {
                $auth->shouldUse($previousGuard);
                $auth->forgetGuards();
                config()->offsetUnset("auth.guards.{$guardName}");
            }
        }

        return self::SUCCESS;
    }

    private function resolveActor(string $identifier): ?User
    {
        if ($identifier === '') {
            return null;
        }

        $query = User::query();
        if (ctype_digit($identifier)) {
            $query->whereKey((int) $identifier);
        } elseif (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $query->where('email', $identifier);
        } else {
            return null;
        }

        return $query
            ->where('is_super', true)
            ->whereNull('blocked_at')
            ->whereNull('withdrawn_at')
            ->first();
    }
}
