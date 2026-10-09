<?php

namespace Modules\Raonslab\TravelLab\Database\Seeders;

use App\Traits\HasSampleSeeders;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use HasSampleSeeders;

    public function run(): void
    {
        if ($this->shouldIncludeSample()) {
            $this->call(SyntheticCatalogSeeder::class);
        }
    }
}
