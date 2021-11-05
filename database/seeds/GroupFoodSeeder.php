<?php

use Illuminate\Database\Seeder;

use App\Food_Group;

class GroupFoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Food_Group::create(['name'=>'Frutas']);
        Food_Group::create(['name'=>'Verduras']);
        Food_Group::create(['name'=>'Cereales']);
        Food_Group::create(['name'=>'Proteinas']);
        Food_Group::create(['name'=>'Otros']);
    }
}
