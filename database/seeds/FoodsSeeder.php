<?php

use Illuminate\Database\Seeder;

use App\Food;

class FoodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Foods::create(['id_group'=>'', 'item'=>'', 'portion'=>'', 'kcal'=>'', 'protein'=>'', 'lipid'=>'', 'cho'=>'', 'clna_mg'=>'', 'k_mg'=>'', 'p_mg'=>'', 'ca_mg'=>'']);
        Foods::create(['name'=>'Legumbres']);
        Foods::create(['name'=>'Lácteos']);
        Foods::create(['name'=>'Leche']);
        Foods::create(['name'=>'Verduras']);
        Foods::create(['name'=>'Frutas']);
        Foods::create(['name'=>'Pan_Y_Cereales']);
        Foods::create(['name'=>'Azúcares']);
        Foods::create(['name'=>'Aceites_Y_Grasas']);
        Foods::create(['name'=>'Soporte']);
    }
}
