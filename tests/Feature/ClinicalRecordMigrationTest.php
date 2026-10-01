<?php

namespace Tests\Feature;

use App\Models\ClinicalMeasurement;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Lab values and waist of the clinical record are moved to their histories.
 */
class ClinicalRecordMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_clinical_record_labs_to_lab_results_and_back(): void
    {
        $migration = require database_path('migrations/2026_09_30_000000_move_clinical_record_labs_to_lab_results.php');
        $doctor = User::factory()->create();
        $patients = collect(['11111111-1', '22222222-2'])->map(fn ($rut) => Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => $rut,
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]));

        // State before the migration: lab values inside clinical_records.
        $migration->down();
        DB::table('clinical_records')->insert([
            [
                'patient_id' => $patients[0]->id, 'consultation_reason' => 'Control', 'lab_date' => '2026-08-01',
                'fasting_glucose' => 105, 'fasting_insulin' => 18.2, 'triglycerides' => 180,
                'created_by' => $doctor->id, 'updated_by' => $doctor->id, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'patient_id' => $patients[1]->id, 'consultation_reason' => 'Sin exámenes', 'lab_date' => null,
                'fasting_glucose' => null, 'fasting_insulin' => null, 'triglycerides' => null,
                'created_by' => $doctor->id, 'updated_by' => $doctor->id, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('clinical_records', 'fasting_glucose'));
        $result = LabResult::sole();
        $this->assertSame($patients[0]->id, $result->patient_id);
        $this->assertSame('2026-08-01', $result->taken_at->toDateString());
        $this->assertSame(18.2, $result->fasting_insulin);
        $this->assertSame(4.72, $result->homaIr());
        $this->assertSame($doctor->id, $result->created_by);

        // Rollback: the record gets the latest result back and the moved row is removed.
        $migration->down();

        $this->assertSame(0, DB::table('lab_results')->count());
        $this->assertEquals(18.2, DB::table('clinical_records')->where('patient_id', $patients[0]->id)->value('fasting_insulin'));
        $this->assertNull(DB::table('clinical_records')->where('patient_id', $patients[1]->id)->value('fasting_insulin'));

        $migration->up(); // leave the schema as the other tests expect
    }

    public function test_moves_clinical_record_waist_to_measurements_and_back(): void
    {
        $migration = require database_path('migrations/2026_09_30_000001_move_clinical_record_waist_to_measurements.php');
        $doctor = User::factory()->create();
        $patients = collect(['11111111-1', '22222222-2'])->map(fn ($rut) => Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Rojas', 'rut' => $rut,
            'address' => 'Calle 1', 'gender' => 'M', 'birthdate' => '1990-01-01',
        ]));

        $migration->down();
        DB::table('clinical_records')->insert([
            [
                'patient_id' => $patients[0]->id, 'consultation_reason' => 'Control', 'waist_cm' => 94.5,
                'created_by' => $doctor->id, 'updated_by' => $doctor->id, 'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-20 12:30:00',
            ],
            [
                'patient_id' => $patients[1]->id, 'consultation_reason' => 'Sin cintura', 'waist_cm' => null,
                'created_by' => $doctor->id, 'updated_by' => $doctor->id, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        // A measurement registered before the migration is kept.
        $patients[0]->measurements()->create(['measured_at' => '2026-08-01', 'weight_kg' => 82]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('clinical_records', 'waist_cm'));
        $moved = ClinicalMeasurement::whereNotNull('waist_cm')->sole();
        $this->assertSame($patients[0]->id, $moved->patient_id);
        $this->assertSame('2026-09-20', $moved->measured_at->toDateString());
        $this->assertSame(94.5, $moved->waist_cm);
        $this->assertSame(2, ClinicalMeasurement::count());

        $migration->down();

        $this->assertSame(1, ClinicalMeasurement::count());
        $this->assertEquals(94.5, DB::table('clinical_records')->where('patient_id', $patients[0]->id)->value('waist_cm'));
        $this->assertNull(DB::table('clinical_records')->where('patient_id', $patients[1]->id)->value('waist_cm'));

        $migration->up(); // leave the schema as the other tests expect
    }
}
