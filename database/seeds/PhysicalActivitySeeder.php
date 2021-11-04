<?php

use Illuminate\Database\Seeder;

App\Physical_Activity;

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
