<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Services\FoodCatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FoodCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = "id,name,group,group_id,portion,grams,kcal,protein,fat,saturated_fat,cho,glycemic_index,sodium_mg,potassium_mg,phosphorus_mg,calcium_mg\n";

    private function csv(string ...$lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'foods');
        file_put_contents($path, self::HEADER.implode("\n", $lines)."\n");

        return $path;
    }

    public function test_imports_the_shared_catalog_with_its_ids(): void
    {
        $this->artisan('foods:import')->assertSuccessful();

        $lines = count(file(base_path(FoodCatalogImporter::PATH), FILE_SKIP_EMPTY_LINES)) - 1;
        $this->assertSame($lines, Food::count());
        $bread = Food::find(12);
        $this->assertSame(['Pan Marraqueta', 'Pan', 75], [$bread->name, $bread->item, (int) $bread->glycemic_index]);
        $this->assertEquals(140, $bread->kcal);
        $this->assertEquals(7.5, Food::where('name', 'Arvejas Cocidas')->value('clna_mg'));
        // Unknown grams are null; unknown minerals, 0 as in the legacy columns.
        $oil = Food::where('name', 'Aceites')->first();
        $this->assertNull($oil->gr);
        $this->assertEquals(0, $oil->clna_mg);
        $this->assertEquals(140, Food::where('name', 'Mote Crudo')->value('kcal'));
    }

    public function test_updates_by_id_and_keeps_foods_that_are_not_in_the_file(): void
    {
        Food::forceCreate(['id' => 1, 'id_group' => 4, 'name' => 'Pollo', 'item' => 'Carnes', 'portion' => '1', 'kcal' => '60', 'protein' => '11', 'lipid' => '2', 'cho' => '1', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
        Food::forceCreate(['id' => 9, 'id_group' => 2, 'name' => 'Zanahoria', 'item' => 'Verduras', 'portion' => '1', 'kcal' => '60', 'protein' => '4', 'lipid' => '0', 'cho' => '14', 'clna_mg' => '0', 'k_mg' => '0', 'p_mg' => '0', 'ca_mg' => '0', 'gr' => '50']);
        $path = $this->csv('1,Pollo,Carnes,4,1,50,65,11,2,0.5,1,,,,,', '2,Pavo,Carnes,4,1,50,"65,0",11,2,0.5,1,,,,,');

        $result = app(FoodCatalogImporter::class)->import($path);

        $this->assertSame([1, 1, 0], [$result['created'], $result['updated'], $result['unchanged']]);
        $this->assertSame([9], $result['absent']->pluck('id')->all());
        $this->assertEquals(65, Food::find(1)->kcal);
        $this->assertEquals(0.5, Food::find(1)->saturated_fat);
        $this->assertSame(3, Food::count());

        $again = app(FoodCatalogImporter::class)->import($path);
        $this->assertSame([0, 0, 2], [$again['created'], $again['updated'], $again['unchanged']]);
    }

    public function test_rejects_a_malformed_catalog_without_writing(): void
    {
        $path = $this->csv(
            '1,Pollo,Carnes,4,1,50,sesenta,11,2,0.5,1,,,,,',
            '2,,Carnes,4,1,50,65,11,2,0.5,1,,,,,',
            '2,Pavo,Carnes,4,1,50,65,11,2,0.5,1,,,,,',
            '3,Huevo,Carnes,4,1,50,65,11,2',
        );

        try {
            app(FoodCatalogImporter::class)->import($path);
            $this->fail('A malformed catalog was imported');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Línea 2 (Pollo): kcal no es un número válido (sesenta).', $e->getMessage());
            $this->assertStringContainsString('Línea 3 (?): falta name.', $e->getMessage());
            $this->assertStringContainsString('Línea 4 (Pavo): id 2 repetido (línea 3).', $e->getMessage());
            $this->assertStringContainsString('Línea 5: 9 celdas, se esperaban 16.', $e->getMessage());
        }
        $this->assertSame(0, Food::count());

        $this->artisan('foods:import', ['--path' => $path])->assertFailed();
    }

    public function test_rejects_unknown_columns(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'foods');
        file_put_contents($path, "id,name,group,group_id,kcal,protein,fat,glicemic_index\n1,Pollo,Carnes,4,65,11,2,\n");

        $this->expectExceptionMessage('Columna desconocida: glicemic_index. Falta la columna cho.');
        app(FoodCatalogImporter::class)->import($path);
    }
}
