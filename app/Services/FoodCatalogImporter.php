<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads the food composition catalog (shared/food_catalog.csv, exchange portions) into the
 * foods table, by id. The expert service reads the same file (expert/app/catalog.py, GET /foods),
 * which also reports doubtful data. Foods of the table that are not in the file are kept:
 * saved meal plans and the editor refer to them by id.
 */
class FoodCatalogImporter
{
    public const PATH = 'shared/food_catalog.csv';

    /** CSV column => foods column */
    private const COLUMNS = [
        'id' => 'id',
        'name' => 'name',
        'group' => 'item',
        'group_id' => 'id_group',
        'portion' => 'portion',
        'grams' => 'gr',
        'kcal' => 'kcal',
        'protein' => 'protein',
        'fat' => 'lipid',
        'saturated_fat' => 'saturated_fat',
        'cho' => 'cho',
        'glycemic_index' => 'glycemic_index',
        'allergens' => 'allergens',
        'price' => 'price',
        'sodium_mg' => 'clna_mg',
        'potassium_mg' => 'k_mg',
        'phosphorus_mg' => 'p_mg',
        'calcium_mg' => 'ca_mg',
    ];

    private const REQUIRED = ['id', 'name', 'group', 'group_id', 'kcal', 'protein', 'fat', 'cho'];

    /** Legacy NOT NULL columns: an unknown value is stored as 0, as the old seeder did. */
    private const ZERO_WHEN_EMPTY = ['portion', 'clna_mg', 'k_mg', 'p_mg', 'ca_mg'];

    /**
     * @return array{created: int, updated: int, unchanged: int, absent: Collection<int, Food>}
     *
     * @throws RuntimeException listing every malformed row; nothing is written then
     */
    public function import(?string $path = null): array
    {
        $rows = $this->rows($path ?? base_path(self::PATH));
        $counts = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::transaction(function () use ($rows, &$counts) {
            foreach ($rows as $id => $attributes) {
                $food = Food::find($id) ?? (new Food)->forceFill(['id' => $id]);
                $key = ! $food->exists ? 'created' : ($this->changes($food, $attributes) ? 'updated' : 'unchanged');
                $food->fill($attributes)->save();
                $counts[$key]++;
            }
        });

        return [...$counts, 'absent' => Food::whereNotIn('id', array_keys($rows))->orderBy('id')->get()];
    }

    /**
     * Rows of the CSV as foods attributes, keyed by id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(string $path): array
    {
        if (! is_readable($path)) {
            throw new RuntimeException("No se encontró el catálogo de alimentos ({$path}).");
        }

        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
        $file->setCsvControl(',', '"', '');
        $header = array_map('trim', $file->current() ?: []);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? ''); // BOM of spreadsheets

        $errors = [
            ...array_map(fn ($c) => "Columna desconocida: {$c}.", array_diff($header, array_keys(self::COLUMNS))),
            ...array_map(fn ($c) => "Falta la columna {$c}.", array_diff(self::REQUIRED, $header)),
        ];
        if ($errors) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $rows = [];
        $lines = [];
        for ($file->next(); $file->valid(); $file->next()) {
            $line = $file->key() + 1;
            $cells = array_map('trim', $file->current());
            if ($cells === [''] || $cells === []) {
                continue;
            }
            if (count($cells) !== count($header)) {
                $errors[] = "Línea {$line}: ".count($cells).' celdas, se esperaban '.count($header).'.';

                continue;
            }

            $values = array_combine($header, $cells);
            $name = $values['name'] ?: '?';
            $row = [];
            $invalid = [];
            foreach ($values as $column => $value) {
                $field = self::COLUMNS[$column];
                if (in_array($column, ['name', 'group', 'portion', 'allergens'], true)) {
                    $row[$field] = $value === '' ? null : $value;

                    continue;
                }
                $value = str_replace(',', '.', $value);
                if ($value !== '' && (! is_numeric($value) || $value < 0)) {
                    $errors[] = "Línea {$line} ({$name}): {$column} no es un número válido ({$value}).";
                    $invalid[] = $column;
                }
                $row[$field] = $value === '' || in_array($column, $invalid, true) ? null : $value + 0;
            }

            foreach (array_diff(self::REQUIRED, $invalid) as $column) {
                if ($row[self::COLUMNS[$column]] === null) {
                    $errors[] = "Línea {$line} ({$name}): falta {$column}.";
                }
            }
            $id = $row['id'];
            if ($id === null) {
                continue;
            }
            if (! is_int($id)) {
                $errors[] = "Línea {$line} ({$name}): el id debe ser entero.";

                continue;
            }
            if (isset($lines[$id])) {
                $errors[] = "Línea {$line} ({$name}): id {$id} repetido (línea {$lines[$id]}).";

                continue;
            }
            $lines[$id] = $line;

            unset($row['id']);
            foreach (self::ZERO_WHEN_EMPTY as $field) {
                $row[$field] ??= 0;
            }
            $rows[$id] = $row;
        }

        if ($errors) {
            throw new RuntimeException(implode(' ', $errors));
        }
        if (! $rows) {
            throw new RuntimeException('El catálogo no tiene alimentos.');
        }

        return $rows;
    }

    /**
     * The foods columns are strings ("7,5", "0.0"): numbers are compared by value.
     */
    private function changes(Food $food, array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $original = $food->getOriginal($key);
            $current = is_string($original) ? str_replace(',', '.', $original) : $original;
            $same = is_numeric($value) && is_numeric($current) ? (float) $value === (float) $current : $value === $original;
            if (! $same) {
                return true;
            }
        }

        return false;
    }
}
