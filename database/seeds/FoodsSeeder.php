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
        Foods::create(['id_group'=>'6','name'=>'Carnes y huevo','item'=>'Baja en Grasa', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'0', 'clna_mg'=>'120', 'k_mg'=>'200', 'p_mg'=>'70', 'ca_mg'=>'7']);
        Foods::create(['id_group'=>'','name'=>'','item'=>'', 'portion'=>'', 'kcal'=>'', 'protein'=>'', 'lipid'=>'', 'cho'=>'', 'clna_mg'=>'', 'k_mg'=>'', 'p_mg'=>'', 'ca_mg'=>'']);
        Foods::create(['id_group'=>'','name'=>'','item'=>'', 'portion'=>'', 'kcal'=>'', 'protein'=>'', 'lipid'=>'', 'cho'=>'', 'clna_mg'=>'', 'k_mg'=>'', 'p_mg'=>'', 'ca_mg'=>'']);
        Foods::create(['id_group'=>'','name'=>'','item'=>'', 'portion'=>'', 'kcal'=>'', 'protein'=>'', 'lipid'=>'', 'cho'=>'', 'clna_mg'=>'', 'k_mg'=>'', 'p_mg'=>'', 'ca_mg'=>'']);
        
    }
}
