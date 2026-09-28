<?php

namespace Modules\Raonslab\Product\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LegacyConsultationAudit
{
    public function hasData(): bool
    {
        try {
            return Schema::hasTable('raonslab_product_consultations')
                && DB::table('raonslab_product_consultations')->limit(1)->exists();
        } catch (Throwable) {
            return true;
        }
    }
}
