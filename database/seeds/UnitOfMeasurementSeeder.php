<?php

use Illuminate\Database\Seeder;

use App\Unit_Of_Measurement;

class UnitOfMeasurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Unit_Of_Measurement::create(['name'=>'Pieza']);
        Unit_Of_Measurement::create(['name'=>'Gr']);
        Unit_Of_Measurement::create(['name'=>'Kg']);
        Unit_Of_Measurement::create(['name'=>'Ml']);
        Unit_Of_Measurement::create(['name'=>'L']);
        Unit_Of_Measurement::create(['name'=>'Cucharadita']);
        Unit_Of_Measurement::create(['name'=>'Cucharada_Sopera']);
        Unit_Of_Measurement::create(['name'=>'Oz']);
        Unit_Of_Measurement::create(['name'=>'Lb']);
        Unit_Of_Measurement::create(['name'=>'Taza']);
        Unit_Of_Measurement::create(['name'=>'Pizca']);
    }
}
