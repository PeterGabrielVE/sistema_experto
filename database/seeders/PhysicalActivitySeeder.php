<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Physical_Activity;

class PhysicalActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Physical_Activity::create(['name'=>'Resposo']);
        Physical_Activity::create(['name'=>'Ligera']);
        Physical_Activity::create(['name'=>'Moderada']);
        Physical_Activity::create(['name'=>'Intensa']);
    }
}
