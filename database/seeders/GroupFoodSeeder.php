<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Food_Group;

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
        Food_Group::create(['name'=>'Legumbres']);
        Food_Group::create(['name'=>'Lácteos']);
        Food_Group::create(['name'=>'Leche']);
        Food_Group::create(['name'=>'Azúcares']);
        Food_Group::create(['name'=>'Aceites']);
        Food_Group::create(['name'=>'Bebidas']);



    }
}
