<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lab results now live in lab_results (one row per sample date). The single set of
 * values stored in the clinical record (ficha clínica) is moved there as one result.
 */
return new class extends Migration
{
    private const ANALYTES = ['fasting_glucose', 'fasting_insulin', 'hba1c', 'total_cholesterol', 'hdl', 'ldl', 'triglycerides'];

    /** Marks the moved rows so the migration can be reversed. */
    private const NOTE = 'Migrado desde la ficha clínica.';

    public function up(): void
    {
        DB::transaction(function () {
            DB::table('clinical_records')
                ->where(function ($q) {
                    foreach (self::ANALYTES as $field) {
                        $q->orWhereNotNull($field);
                    }
                })
                ->orderBy('id')
                ->each(function (object $record) {
                    DB::table('lab_results')->insert([
                        'patient_id' => $record->patient_id,
                        // The record form required lab_date with any value; fall back just in case.
                        'taken_at' => $record->lab_date ?? substr((string) ($record->updated_at ?? now()), 0, 10),
                        ...array_combine(self::ANALYTES, array_map(fn ($field) => $record->{$field}, self::ANALYTES)),
                        'notes' => self::NOTE,
                        'created_by' => $record->created_by,
                        'updated_by' => $record->updated_by,
                        'created_at' => $record->created_at,
                        'updated_at' => $record->updated_at,
                    ]);
                });
        });

        Schema::table('clinical_records', function (Blueprint $table) {
            $table->dropColumn(['lab_date', ...self::ANALYTES]);
        });
    }

    public function down(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            $table->date('lab_date')->nullable()->after('waist_cm');
            $table->decimal('fasting_glucose', 5, 1)->nullable()->after('lab_date');
            $table->decimal('fasting_insulin', 5, 1)->nullable()->after('fasting_glucose');
            $table->decimal('hba1c', 4, 1)->nullable()->after('fasting_insulin');
            $table->decimal('total_cholesterol', 5, 1)->nullable()->after('hba1c');
            $table->decimal('hdl', 5, 1)->nullable()->after('total_cholesterol');
            $table->decimal('ldl', 5, 1)->nullable()->after('hdl');
            $table->decimal('triglycerides', 6, 1)->nullable()->after('ldl');
        });

        DB::transaction(function () {
            // The record keeps the latest result of each patient, as before.
            DB::table('clinical_records')->orderBy('id')->each(function (object $record) {
                $latest = DB::table('lab_results')->where('patient_id', $record->patient_id)
                    ->orderByDesc('taken_at')->orderByDesc('id')->first();

                if ($latest) {
                    DB::table('clinical_records')->where('id', $record->id)->update([
                        'lab_date' => $latest->taken_at,
                        ...array_combine(self::ANALYTES, array_map(fn ($field) => $latest->{$field}, self::ANALYTES)),
                    ]);
                }
            });

            DB::table('lab_results')->where('notes', self::NOTE)->delete();
        });
    }
};
