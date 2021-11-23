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
        Food::create(['id_group'=>'4','name'=>'Carne de Vacuno','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'4','name'=>'Cerdo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'4','name'=>'Pollo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'4','name'=>'Pavo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'4','name'=>'Jamon de Pavo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'4','name'=>'Carne Vegetal','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'25']);
        Food::create(['id_group'=>'4','name'=>'Atún en Agua','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'60']);
        Food::create(['id_group'=>'4','name'=>'Pescados en General','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'80']);
        Food::create(['id_group'=>'4','name'=>'Mariscos Frescos','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'60']);
        Food::create(['id_group'=>'4','name'=>'Clara de Huevo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'100']);
        Food::create(['id_group'=>'4','name'=>'Huevo','item'=>'Carnes', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);

        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Baja en Grasa', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'2', 'cho'=>'0', 'clna_mg'=>'120', 'k_mg'=>'200', 'p_mg'=>'70', 'ca_mg'=>'7']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Visceras', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Huevo', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Pescados', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'ALTAS en Grasa', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Visceras', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'4','name'=>'Carnes y huevo','item'=>'Pescados', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Baja', 'portion'=>'2', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80']);
        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Moderada', 'portion'=>'2', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'500', 'p_mg'=>'68', 'ca_mg'=>'54']);
        Foods::create(['id_group'=>'2','name'=>'Verduras','item'=>'Alta,', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'3','name'=>'Pan Marraqueta','item'=>'Pan', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63','gr'=>'50']);
        Foods::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Cereales', 'portion'=>'1,5', 'kcal'=>'210', 'protein'=>'4,5', 'lipid'=>'1,5', 'cho'=>'45', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39']);
        Foods::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Papa,', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Baja', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Moderada', 'portion'=>'3', 'kcal'=>'195', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'45', 'clna_mg'=>'15', 'k_mg'=>'675', 'p_mg'=>'63', 'ca_mg'=>'54']);
        Foods::create(['id_group'=>'1','name'=>'Frutas','item'=>'Alta,', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Foods::create(['id_group'=>'5','name'=>'Frutas','item'=>'Baja', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Foods::create(['id_group'=>'5','name'=>'Frutas','item'=>'Moderada', 'portion'=>'3', 'kcal'=>'195', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'45', 'clna_mg'=>'15', 'k_mg'=>'675', 'p_mg'=>'63', 'ca_mg'=>'54']);
        Foods::create(['id_group'=>'5','name'=>'Frutas','item'=>'Alta,', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'4','name'=>'Proteínas','item'=>'Carnes altas en Grasa', 'portion'=>'1', 'kcal'=>'120', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'120', 'k_mg'=>'200', 'p_mg'=>'70', 'ca_mg'=>'7']);
        Food::create(['id_group'=>'4','name'=>'Proteínas','item'=>'Carnes bajas en grasas', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'11', 'lipid'=>'8', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'2','name'=>'Cebolla','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'550', 'p_mg'=>'68', 'ca_mg'=>'54','gr'=>'60']);
        Food::create(['id_group'=>'2','name'=>'Tomate','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'550', 'p_mg'=>'68', 'ca_mg'=>'54','gr'=>'120']);
        Food::create(['id_group'=>'2','name'=>'Zanahoria','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'550', 'p_mg'=>'68', 'ca_mg'=>'54','gr'=>'50']);

        Food::create(['id_group'=>'2','name'=>'Acelga','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'110']);
        Food::create(['id_group'=>'2','name'=>'Betarraga','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'90']);
        Food::create(['id_group'=>'2','name'=>'Brocoli, Coliflor','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'100-110']);
        Food::create(['id_group'=>'2','name'=>'Champiñones','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'110']);
        Food::create(['id_group'=>'2','name'=>'Espárragos','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'100']);
        Food::create(['id_group'=>'2','name'=>'Espinaca','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'130']);
        Food::create(['id_group'=>'2','name'=>'Porotos Verdes','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'70']);
        Food::create(['id_group'=>'2','name'=>'Zanahoria','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'50']);
        Food::create(['id_group'=>'2','name'=>'Zapallo Italiano','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'150']);
        Food::create(['id_group'=>'2','name'=>'Zapallo','item'=>'Verduras', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80','gr'=>'70']);
        Food::create(['id_group'=>'2','name'=>'Verduras','item'=>'Verduras en General', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'16', 'k_mg'=>'400', 'p_mg'=>'28', 'ca_mg'=>'80']);
        Food::create(['id_group'=>'2','name'=>'Verduras','item'=>'Verduras libre consumo', 'portion'=>'1', 'kcal'=>'60', 'protein'=>'4', 'lipid'=>'0', 'cho'=>'14', 'clna_mg'=>'30', 'k_mg'=>'550', 'p_mg'=>'68', 'ca_mg'=>'54']);

        Food::create(['id_group'=>'3','name'=>'Pan Marraqueta','item'=>'Pan', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63','gr'=>'50']);
        Food::create(['id_group'=>'3','name'=>'Pan Molde','item'=>'Pan', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63','gr'=>'60']);
        Food::create(['id_group'=>'3','name'=>'Arroz o Fideos Cocidos','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'50']);
        Food::create(['id_group'=>'3','name'=>'Galletas de Agua o Soda','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'40']);
        Food::create(['id_group'=>'3','name'=>'Galletas Integrales','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'40']);
        Food::create(['id_group'=>'3','name'=>'Arvejas Cocidas','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'190']);
        Food::create(['id_group'=>'3','name'=>'Choclo Cocido','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'160']);
        Food::create(['id_group'=>'3','name'=>'Habas Cocidas','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39','gr'=>'150']);
        Food::create(['id_group'=>'3','name'=>'Papas Cocidas','item'=>'Cereales', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'150']);
        Food::create(['id_group'=>'3','name'=>'Harina Tostada','item'=>'Pan', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63','gr'=>'40']);
        Food::create(['id_group'=>'3','name'=>'Avena Cruda','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'2226', 'k_mg'=>'135', 'p_mg'=>'120', 'ca_mg'=>'63','gr'=>'50']);
        Food::create(['id_group'=>'3','name'=>'Mote Crudo','item'=>'Cereales', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'100']);
        Food::create(['id_group'=>'3','name'=>'Quinoa Cruda','item'=>'Cereales', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'40']);
        Food::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Cereales', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'3', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'7,5', 'k_mg'=>'69', 'p_mg'=>'75', 'ca_mg'=>'39']);
        Food::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Papa', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Galletas altas en grasas', 'portion'=>'1', 'kcal'=>'230', 'protein'=>'3', 'lipid'=>'11', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'3','name'=>'Pan Y Cereales','item'=>'Galletas bajas en grasas', 'portion'=>'1', 'kcal'=>'185', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'1','name'=>'Pera, Durazno, Manzana, Naranja','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'130']);
        Food::create(['id_group'=>'1','name'=>'Cerezas','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'90']);
        Food::create(['id_group'=>'1','name'=>'Kiwi','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'100']);
        Food::create(['id_group'=>'1','name'=>'Melon, Sandía','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'180-200']);
        Food::create(['id_group'=>'1','name'=>'Piña','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'120']);
        Food::create(['id_group'=>'1','name'=>'Platano','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'60']);
        Food::create(['id_group'=>'1','name'=>'Uvas','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'90']);
        Food::create(['id_group'=>'1','name'=>'Mandarina','item'=>'', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'1','name'=>'Frutas','item'=>'Frutas', 'portion'=>'1', 'kcal'=>'65', 'protein'=>'1', 'lipid'=>'0', 'cho'=>'15', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'6','name'=>'Arveja Seca Cruda','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Poroto Crudo','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Lenteja Cruda','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Garbanzo Crudo','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Haba Seca Cruda','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'60']);
        Food::create(['id_group'=>'6','name'=>'Poroto Cocido','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'100']);
        Food::create(['id_group'=>'6','name'=>'Lenteja Cocida','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'140']);
        Food::create(['id_group'=>'6','name'=>'Garbanzo Cocido','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'130']);
        Food::create(['id_group'=>'6','name'=>'Harina de Arveja Cocida','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Harina de Garbanzo Precocida','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);
        Food::create(['id_group'=>'6','name'=>'Harina de Lenteja Precocida','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'50']);

        Food::create(['id_group'=>'6','name'=>'Legumbres','item'=>'Legumbres', 'portion'=>'1', 'kcal'=>'170', 'protein'=>'11', 'lipid'=>'1', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'7','name'=>'Leche','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Yogurt Natural o Diet','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Queso Mantecoso o Chanco','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'80', 'protein'=>'5', 'lipid'=>'0', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Queso Crema','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Leche de Soya','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Quesillo','item'=>'Lácteos', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);

        Food::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Altos en grasas', 'portion'=>'1', 'kcal'=>'110', 'protein'=>'5', 'lipid'=>'6', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Medios en grasas', 'portion'=>'1', 'kcal'=>'85', 'protein'=>'5', 'lipid'=>'3', 'cho'=>'9', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Medios en grasas ricos en CHO', 'portion'=>'1', 'kcal'=>'167', 'protein'=>'5', 'lipid'=>'3', 'cho'=>'30', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Quesos', 'portion'=>'1', 'kcal'=>'80', 'protein'=>'5', 'lipid'=>'0', 'cho'=>'1', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'7','name'=>'Lácteos','item'=>'Bajos en grasas', 'portion'=>'1', 'kcal'=>'70', 'protein'=>'7', 'lipid'=>'0', 'cho'=>'10', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);


        Food::create(['id_group'=>'8','name'=>'Leche','item'=>'Bajo', 'portion'=>'1', 'kcal'=>'70', 'protein'=>'7', 'lipid'=>'0', 'cho'=>'10', 'clna_mg'=>'290', 'k_mg'=>'300', 'p_mg'=>'180', 'ca_mg'=>'240']);
        Food::create(['id_group'=>'8','name'=>'Leche','item'=>'Moderado', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'8','name'=>'Leche','item'=>'Alto', 'portion'=>'0', 'kcal'=>'0', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'9','name'=>'Azúcares','item'=>'Azúcares', 'portion'=>'1', 'kcal'=>'20', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'5', 'clna_mg'=>'290', 'k_mg'=>'300', 'p_mg'=>'180', 'ca_mg'=>'240']);
        Food::create(['id_group'=>'9','name'=>'Azúcares','item'=>'Azúcares con distintos aportes (1 grupo)', 'portion'=>'1', 'kcal'=>'130', 'protein'=>'2', 'lipid'=>'5', 'cho'=>'20', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'9','name'=>'Azúcares','item'=>'Azúcares con distintos aportes (2 grupo)', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'2', 'lipid'=>'10', 'cho'=>'20', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'10','name'=>'Aceites','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'0']);
        Food::create(['id_group'=>'10','name'=>'Almendras','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'25']);
        Food::create(['id_group'=>'10','name'=>'Avellana','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'30']);
        Food::create(['id_group'=>'10','name'=>'Maní sin sal','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'30']);
        Food::create(['id_group'=>'10','name'=>'Nuez','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'25']);
        Food::create(['id_group'=>'10','name'=>'Pístacho','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'30']);
        Food::create(['id_group'=>'10','name'=>'Aceituna','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'115']);
        Food::create(['id_group'=>'10','name'=>'Palta','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0','gr'=>'90']);

        Food::create(['id_group'=>'10','name'=>'Aceites','item'=>'Aceites', 'portion'=>'1', 'kcal'=>'180', 'protein'=>'0', 'lipid'=>'20', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'10','name'=>'Aceites','item'=>'Alimentos ricos en lípidos', 'portion'=>'1', 'kcal'=>'175', 'protein'=>'5', 'lipid'=>'15', 'cho'=>'5', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);

        Food::create(['id_group'=>'11','name'=>'Bebidas','item'=>'Bebidas altas en CHO', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'20', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'11','name'=>'Bebidas','item'=>'Bebidas medias en CHO', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'3,5', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);
        Food::create(['id_group'=>'11','name'=>'Bebidas','item'=>'Bebidas solo alcohol', 'portion'=>'1', 'kcal'=>'140', 'protein'=>'0', 'lipid'=>'0', 'cho'=>'0', 'clna_mg'=>'0', 'k_mg'=>'0', 'p_mg'=>'0', 'ca_mg'=>'0']);



    }
}
