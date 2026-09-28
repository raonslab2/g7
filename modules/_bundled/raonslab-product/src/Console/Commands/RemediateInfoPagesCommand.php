<?php

namespace Modules\Raonslab\Product\Console\Commands;

use Illuminate\Console\Command;
use JsonException;
use Modules\Raonslab\Product\Console\Concerns\ActsAsPageActor;
use Modules\Raonslab\Product\Exceptions\InfoPageRemediationConflict;
use Modules\Raonslab\Product\Services\InfoPageRemediator;
use Throwable;

/**
 * 그대로 남은 G7 샘플 Page 4종을 승인된 외부 payload 로 한 번 교체한다.
 * module install/update 는 이 명령을 호출하지 않는다. 운영자가 백업 뒤 직접 실행한다.
 */
class RemediateInfoPagesCommand extends Command
{
    use ActsAsPageActor;

    protected $signature = 'raonslab-product:remediate-info-pages
        {payload : Absolute path to the approved about/faq/contact/refund JSON payload}
        {--actor= : Active super administrator ID or exact email for Page attribution}
        {--dry-run : Lock and classify all four Pages, report, and write nothing}';

    protected $description = 'Replace the untouched G7 sample about/faq/contact/refund Pages through PageService (all-or-nothing)';

    public function handle(InfoPageRemediator $remediator): int
    {
        $path = (string) $this->argument('payload');
        if (! str_starts_with($path, '/') || ! is_file($path) || ! is_readable($path)) {
            $this->error('The payload must be an absolute path to a readable file.');

            return self::FAILURE;
        }

        $actor = $this->resolveActor((string) $this->option('actor'));
        if (! $actor) {
            $this->error('The actor must identify an active super administrator by ID or exact email.');

            return self::FAILURE;
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new JsonException('The payload root must be an object.');
            }
            $this->line('payload_sha256: '.hash_file('sha256', $path));

            $results = $this->asPageActor(
                $actor,
                'raonslab-info-page-remediation',
                fn (): array => $remediator->remediate($payload, $actor, (bool) $this->option('dry-run')),
            );
        } catch (InfoPageRemediationConflict $conflict) {
            foreach ($conflict->conflicts as $slug => $reason) {
                $this->line("{$slug}: conflict ({$reason})");
            }
            $this->error('Aborted without writing any Page.');

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Info Page remediation failed closed: '.$exception->getMessage());

            return self::FAILURE;
        }

        foreach ($results as $slug => $status) {
            $this->line("{$slug}: {$status}");
        }

        return self::SUCCESS;
    }
}
