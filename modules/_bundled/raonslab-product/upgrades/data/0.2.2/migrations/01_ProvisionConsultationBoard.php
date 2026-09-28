<?php

namespace App\Upgrades\Data\Ext\Modules\RaonslabProduct\V0_2_2\Migrations;

use App\Extension\Upgrade\DataMigration;
use App\Extension\UpgradeContext;
use Modules\Raonslab\Product\Services\ConsultationBoardProvisioner;

class ProvisionConsultationBoard implements DataMigration
{
    public function name(): string
    {
        return 'ProvisionConsultationBoard';
    }

    public function run(UpgradeContext $context): void
    {
        app(ConsultationBoardProvisioner::class)->ensureReady();

        $context->logger->info('[raonslab-product:0.2.2] 상담 게시판 준비 완료');
    }
}
