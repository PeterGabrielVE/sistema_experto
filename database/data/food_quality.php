<?php

/**
 * Glycemic index (glucose = 100) and saturated fat (g per exchange portion) of the food
 * catalog, by foods.name. Approximate reference values: GI from the international tables
 * (Atkinson et al., Am J Clin Nutr 2021); saturated fat as the usual saturated share of
 * each food applied to the fat of its exchange portion. null GI: no carbohydrates to speak of.
 * Used by the migration that adds the columns and by FoodsSeeder.
 *
 * @return array<string, array{0: int|null, 1: float}>
 */
return [
    // Carnes (2 g de grasa por porción)
    'Carne de Vacuno' => [null, 0.8],
    'Cerdo' => [null, 0.7],
    'Pollo' => [null, 0.5],
    'Pavo' => [null, 0.5],
    'Jamon de Pavo' => [null, 0.6],
    'Carne Vegetal' => [null, 0.3],
    'Atún en Agua' => [null, 0.3],
    'Pescados en General' => [null, 0.4],
    'Mariscos Frescos' => [null, 0.3],
    'Clara de Huevo' => [null, 0.0],
    'Huevo' => [null, 0.7],
    // Pan y cereales (1 g de grasa)
    'Pan Marraqueta' => [75, 0.2],
    'Pan Molde' => [72, 0.2],
    'Harina Tostada' => [65, 0.2],
    'Arroz o Fideos Cocidos' => [60, 0.2],
    'Galletas de Agua o Soda' => [70, 0.4],
    'Galletas Integrales' => [62, 0.4],
    'Arvejas Cocidas' => [51, 0.1],
    'Choclo Cocido' => [52, 0.2],
    'Habas Cocidas' => [79, 0.1],
    'Papas Cocidas' => [78, 0.1],
    'Avena Cruda' => [55, 0.2],
    'Mote Crudo' => [55, 0.2],
    'Quinoa Cruda' => [53, 0.1],
    // Verduras
    'Cebolla' => [15, 0.0],
    'Tomate' => [15, 0.0],
    'Zanahoria' => [39, 0.0],
    'Acelga' => [15, 0.0],
    'Betarraga' => [64, 0.0],
    'Brocoli, Coliflor' => [15, 0.0],
    'Champiñones' => [15, 0.0],
    'Espárragos' => [15, 0.0],
    'Espinaca' => [15, 0.0],
    'Porotos Verdes' => [30, 0.0],
    'Zapallo Italiano' => [15, 0.0],
    'Zapallo' => [75, 0.0],
    // Frutas
    'Pera, Durazno, Manzana, Naranja' => [38, 0.0],
    'Cerezas' => [22, 0.0],
    'Kiwi' => [50, 0.0],
    'Melon, Sandía' => [70, 0.0],
    'Piña' => [59, 0.0],
    'Platano' => [51, 0.0],
    'Uvas' => [53, 0.0],
    'Mandarina' => [47, 0.0],
    // Legumbres (1 g de grasa)
    'Arveja Seca Cruda' => [22, 0.1],
    'Poroto Crudo' => [29, 0.1],
    'Lenteja Cruda' => [29, 0.1],
    'Garbanzo Crudo' => [28, 0.1],
    'Haba Seca Cruda' => [40, 0.1],
    'Poroto Cocido' => [29, 0.1],
    'Lenteja Cocida' => [29, 0.1],
    'Garbanzo Cocido' => [28, 0.1],
    'Harina de Arveja Cocida' => [35, 0.1],
    'Harina de Garbanzo Precocida' => [35, 0.1],
    'Harina de Lenteja Precocida' => [35, 0.1],
    // Lácteos (6 g de grasa; el queso mantecoso figura sin grasa en el catálogo)
    'Leche Entera en Polvo' => [39, 3.7],
    'Yogurt Natural o Diet' => [35, 3.5],
    'Queso Mantecoso o Chanco' => [null, 0.0],
    'Queso Crema' => [30, 3.6],
    'Leche de Soya' => [34, 0.9],
    'Quesillo' => [30, 3.5],
    // Aceites y grasas (15 g de grasa)
    'Aceites' => [null, 2.0],
    'Aceite de Oliva' => [null, 2.1],
    'Aceite de Maíz' => [null, 2.0],
    'Almendras' => [null, 1.2],
    'Avellana' => [null, 1.1],
    'Maní sin sal' => [null, 2.1],
    'Nuez' => [null, 1.4],
    'Pístacho' => [null, 1.8],
    'Aceituna' => [null, 2.0],
    'Palta' => [null, 2.1],
    'Aceite de Soya' => [null, 2.3],
    'Aceite de Pepita' => [null, 1.5],
];
