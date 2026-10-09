<?php

namespace Modules\Raonslab\TravelLab\Console\Commands;

use Illuminate\Console\Command;
use Modules\Raonslab\TravelLab\Exceptions\TravelSupportException;
use Modules\Raonslab\TravelLab\Services\TravelSupportProvisioner;

/**
 * LAB 고객지원 게시판 3종과 합성 공지/FAQ 를 준비합니다 (명시 실행 전용, 멱등).
 *
 * 설정 raonslab-travel_lab.support.lab_provisioning=true 이고 --lab-confirm 을 넘겼을 때만
 * 쓰기를 수행한다. 스케줄러·설치·업데이트에 연결하지 않는다.
 */
class ProvisionTravelSupportCommand extends Command
{
    protected $signature = 'raonslab-travel_lab:support-provision
        {--lab-confirm : LAB 환경임을 확인하고 게시판·합성 콘텐츠 생성을 허용합니다}';

    protected $description = '여행 연구소 LAB 고객지원 게시판(공지/FAQ/문의)을 준비합니다';

    public function handle(TravelSupportProvisioner $provisioner): int
    {
        try {
            $report = $provisioner->provision((bool) $this->option('lab-confirm'));
        } catch (TravelSupportException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($report['boards'] as $channel => $board) {
            $this->line(sprintf(
                '%s: board=%s id=%d created=%s seeded=%d skipped=%d',
                $channel,
                $board['slug'],
                $board['id'],
                $board['created'] ? 'yes' : 'no',
                $report['seeded'][$channel],
                $report['skipped'][$channel],
            ));
        }

        return self::SUCCESS;
    }
}
