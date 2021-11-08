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
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Baja en Grasa', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'0', 'clna_mg'=>'120', 'k_mg'=>'200', 'p_mg'=>'70', 'ca_mg'=>'7']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Visceras', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Huevo', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Pescados', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'ALTAS en Grasa', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Visceras', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Pescados', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Baja', 'portion'=>'2', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80']);
        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Moderada', 'portion'=>'2', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'550', 'p_mg'=>'68', 'ca_mg'=>'54']);
        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Alta', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Pan', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63']);
        Foods::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39']);
        Foods::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Papa', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Baja', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Moderada', 'portion'=>'3', 'kcal'=>'195', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'45', 'clna_mg'=>'15', 'k_mg'=>'675', 'p_mg'=>'63', 'ca_mg'=>'54']);
        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Alta', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'6','name'=>'Legumbres','item'=>'Legumbres Secas', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'6','name'=>'Legumbres','item'=>'Legumbres Frescas', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Bajo', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Moderado', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Alto', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'8','name'=>'Leche','item'=>'Bajo', 'portion'=>'1', 'kcal'=>'70', 'protein'=>'7', 'lipid'=>'0', 'cho'=>'10', 'clna_mg'=>'290', 'k_mg'=>'300', 'p_mg'=>'180', 'ca_mg'=>'240']);
        Foods::create(['id_group'=>'8','name'=>'Leche','item'=>'Moderado', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'8','name'=>'Leche','item'=>'Alto', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'9','name'=>'Azúcar','item'=>'Bajo', 'portion'=>'1', 'kcal'=>'70', 'protein'=>'7', 'lipid'=>'0', 'cho'=>'10', 'clna_mg'=>'290', 'k_mg'=>'300', 'p_mg'=>'180', 'ca_mg'=>'240']);
        Foods::create(['id_group'=>'9','name'=>'Chocolate','item'=>'Moderado', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'9','name'=>'Vino Blanco','item'=>'Alto', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'9','name'=>'Vino Tinto','item'=>'Alto', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'10','name'=>'Aceites Y Grasas','item'=>'Aceites', 'portion'=>'6', 'kcal'=>'270', 'protein'=>'0', 'lipid'=>'30', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'10','name'=>'Aceites Y Grasas','item'=>'Mantequilla/Margarina', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'10','name'=>'Aceites Y Grasas','item'=>'Alto en K', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'10','name'=>'Aceites Y Grasas','item'=>'Alto en P', 'portion'=>'1', 'kcal'=>'45', 'protein'=>'0', 'lipid'=>'5', 'cho'=>'0', 'clna_mg'=>'7,5', 'k_mg'=>'157', 'p_mg'=>'123', 'ca_mg'=>'41']);



    }
}
