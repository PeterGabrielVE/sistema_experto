<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Time_Of_Day;

class TimeOfDaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Time_Of_Day::create(['name'=>'Desayuno']);
        Time_Of_Day::create(['name'=>'Colacion_Matutina']);
        Time_Of_Day::create(['name'=>'Almuerzo']);
        Time_Of_Day::create(['name'=>'Colacion_Vespertina']);
        Time_Of_Day::create(['name'=>'Cena']);
        Time_Of_Day::create(['name'=>'Colacion_Nocturna']);
    }
}
