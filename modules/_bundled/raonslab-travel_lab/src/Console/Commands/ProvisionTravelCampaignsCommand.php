<?php

namespace Modules\Raonslab\TravelLab\Console\Commands;

use Illuminate\Console\Command;
use Modules\Raonslab\TravelLab\Exceptions\TravelCampaignException;
use Modules\Raonslab\TravelLab\Services\TravelCampaignProvisioner;

/**
 * LAB 캠페인 Page 두 건을 준비합니다 (명시 실행 전용, 기존 Page 는 건드리지 않음).
 *
 * 설치·업데이트·시더·스케줄러·요청 경로에 연결하지 않는다.
 */
class ProvisionTravelCampaignsCommand extends Command
{
    protected $signature = 'raonslab-travel_lab:campaigns-provision
        {--lab-confirm : 격리 LAB 환경임을 확인하고 합성 캠페인 Page 생성을 허용합니다}
        {--actor= : native Page 읽기·생성 권한을 가진 기존 관리자 사용자 ID}';

    protected $description = '여행 연구소 LAB 캠페인 Page 두 건을 native 페이지 서비스로 준비합니다';

    public function handle(TravelCampaignProvisioner $provisioner): int
    {
        $actor = $this->option('actor');
        $actorId = is_string($actor) && preg_match('/^[1-9][0-9]{0,18}$/', $actor) === 1 ? (int) $actor : null;

        try {
            $report = $provisioner->provision((bool) $this->option('lab-confirm'), $actorId);
        } catch (TravelCampaignException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($report['created'] as $row) {
            $this->line(sprintf('created: %s id=%d', $row['slug'], $row['id']));
        }
        foreach ($report['skipped'] as $row) {
            $this->line(sprintf('skipped (existing, untouched): %s id=%d', $row['slug'], $row['id']));
        }
        $this->info(sprintf('created=%d skipped=%d', count($report['created']), count($report['skipped'])));

        return self::SUCCESS;
    }
}
