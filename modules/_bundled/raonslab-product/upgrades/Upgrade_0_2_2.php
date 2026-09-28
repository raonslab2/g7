<?php

namespace Modules\Raonslab\Product\Upgrades;

use App\Extension\AbstractUpgradeStep;

/**
 * 기존 설치에도 상담 전용 게시판을 lifecycle 안에서 준비합니다.
 *
 * 모든 실행 로직은 data/0.2.2/migrations/ 로 격리합니다.
 *
 * @upgrade-path A
 */
class Upgrade_0_2_2 extends AbstractUpgradeStep {}
