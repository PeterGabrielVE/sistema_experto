<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Waist circumference now lives in clinical_measurements (one row per control).
 * The value stored in the clinical record (ficha clínica) is moved there as one measurement.
 */
return new class extends Migration
{
    /** Marks the moved rows so the migration can be reversed. */
    private const NOTE = 'Migrado desde la ficha clínica.';

    public function up(): void
    {
        DB::transaction(function () {
            DB::table('clinical_records')->whereNotNull('waist_cm')->orderBy('id')->each(function (object $record) {
                DB::table('clinical_measurements')->insert([
                    'patient_id' => $record->patient_id,
                    // The record has no measurement date: use when it was last saved.
                    'measured_at' => substr((string) ($record->updated_at ?? now()), 0, 10),
                    'waist_cm' => $record->waist_cm,
                    'notes' => self::NOTE,
                    'created_by' => $record->created_by,
                    'updated_by' => $record->updated_by,
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ]);
            });
        });

        Schema::table('clinical_records', function (Blueprint $table) {
            $table->dropColumn('waist_cm');
        });
    }

    public function down(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            $table->decimal('waist_cm', 5, 1)->nullable()->after('water_liters');
        });

        DB::transaction(function () {
            // The record keeps the latest waist of each patient, as before.
            DB::table('clinical_records')->orderBy('id')->each(function (object $record) {
                $waist = DB::table('clinical_measurements')->where('patient_id', $record->patient_id)
                    ->whereNotNull('waist_cm')->orderByDesc('measured_at')->orderByDesc('id')->value('waist_cm');

                DB::table('clinical_records')->where('id', $record->id)->update(['waist_cm' => $waist]);
            });

            DB::table('clinical_measurements')->where('notes', self::NOTE)->delete();
        });
    }
};
