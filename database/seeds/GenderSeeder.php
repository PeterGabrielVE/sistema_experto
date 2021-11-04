<?php

use Illuminate\Database\Seeder;

App\Gender;

class GenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Gender::create(['name'=>'Femenino']);
        Gender::create(['name'=>'Masculino']);
    }
}
