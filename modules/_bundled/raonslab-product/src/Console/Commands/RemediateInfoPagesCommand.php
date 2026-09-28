<?php

namespace Modules\Raonslab\Product\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modules\Raonslab\Product\Console\Concerns\ActsAsPageActor;
use Modules\Raonslab\Product\Exceptions\InfoPageRemediationConflict;
use Modules\Raonslab\Product\Services\InfoPageRemediator;
use Modules\Raonslab\Product\Services\NativePageContentPack;
use Throwable;

/**
 * 그대로 남은 G7 샘플 Page 4종을 승인된 content pack 으로 한 번 교체한다.
 * module install/update 는 이 명령을 호출하지 않는다. 운영자가 백업 뒤 직접 실행한다.
 */
class RemediateInfoPagesCommand extends Command
{
    use ActsAsPageActor;

    protected $signature = 'raonslab-product:remediate-info-pages
        {pack : Absolute path to the approved raonslab-product.native-page-content-pack.v1 JSON (about/faq/contact/refund)}
        {--actor= : Active super administrator ID or exact email for Page attribution}
        {--sha256= : Approved SHA-256 of the whole pack file (required unless --dry-run)}
        {--dry-run : Lock and classify all four Pages, report, and write nothing}';

    protected $description = 'Replace the untouched G7 sample about/faq/contact/refund Pages through PageService (all-or-nothing)';

    public function handle(InfoPageRemediator $remediator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $expectedSha256 = $this->option('sha256');
        $expectedSha256 = is_string($expectedSha256) && $expectedSha256 !== '' ? $expectedSha256 : null;
        if (! $dryRun && $expectedSha256 === null) {
            $this->error('Applying requires --sha256 with the approved whole-file SHA-256 of the content pack.');

            return self::FAILURE;
        }

        $actor = $this->resolveActor((string) $this->option('actor'));
        if (! $actor) {
            $this->error('The actor must identify an active super administrator by ID or exact email.');

            return self::FAILURE;
        }

        try {
            $pack = NativePageContentPack::fromFile(
                (string) $this->argument('pack'),
                InfoPageRemediator::AUDITED_BASE_COMMIT,
                $expectedSha256,
            );
        } catch (InvalidArgumentException $exception) {
            $this->error('Content pack rejected: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->line('pack_sha256: '.$pack->sha256.($expectedSha256 === null ? ' (not checked)' : ' (matches approved)'));
        $this->line('schema: '.NativePageContentPack::SCHEMA);
        $this->line('pack_id: '.$pack->packId);
        $this->line('base_commit: '.$pack->baseCommit);

        try {
            $results = $this->asPageActor(
                $actor,
                'raonslab-info-page-remediation',
                fn (): array => $remediator->remediate($pack->pages, $actor, $dryRun),
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
