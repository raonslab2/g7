<?php

namespace Modules\Raonslab\Product\Console\Commands;

use Illuminate\Console\Command;
use Modules\Raonslab\Product\Services\ConsultationBoardProvisioner;
use Modules\Raonslab\Product\Services\ConsultationConfigService;
use Throwable;

/**
 * 공개 상담 접수 활성화 조건을 점검합니다.
 *
 * 설정값(문안·연락처·URL)은 출력하지 않고 조건별 충족 여부만 보고합니다.
 * 요청 HTTPS 조건은 CLI에서 판정할 수 없으므로 외부 경로 smoke로 확인합니다.
 */
class ConsultationReadinessCommand extends Command
{
    protected $signature = 'raonslab-product:consultation-readiness {--json : Print machine-readable result}';

    protected $description = 'Report whether public consultation intake can be enabled, without printing configured values';

    public function handle(ConsultationConfigService $config, ConsultationBoardProvisioner $provisioner): int
    {
        $checks = $config->readinessChecks();

        try {
            $provisioner->requireReady();
            $checks['private_board_ready'] = true;
        } catch (Throwable) {
            $checks['private_board_ready'] = false;
        }

        $pending = array_keys(array_filter($checks, static fn (bool $ok): bool => ! $ok));
        $ready = $pending === [];

        if ($this->option('json')) {
            $this->line(json_encode([
                'ready' => $ready,
                'checks' => $checks,
                'pending' => $pending,
                'request_https' => 'verify-by-external-smoke',
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['check', 'status'], array_map(
                static fn (string $key, bool $ok): array => [$key, $ok ? 'PASS' : 'PENDING'],
                array_keys($checks),
                $checks,
            ));
            $this->line($ready
                ? 'All server-side conditions are met. Verify request HTTPS via the external URL before announcing.'
                : 'Public intake stays closed. Pending: '.implode(', ', $pending));
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }
}
