<?php

use Illuminate\Database\Seeder;

use App\Type_Of_Preparation;

class TypeOfPreparationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Type_Of_Preparation::create(['name'=>'Bebida']);
        Type_Of_Preparation::create(['name'=>'Platillo']);

    }
}
