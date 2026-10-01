<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laboratory results (exámenes de laboratorio): many per patient, one per sample date.
 * Optionally linked to the consultation (diagnoses) where they were reviewed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
            $table->date('taken_at');

            // Glucose metabolism
            $table->decimal('fasting_glucose', 5, 1)->nullable();   // mg/dL
            $table->decimal('fasting_insulin', 5, 1)->nullable();   // µU/mL
            $table->decimal('hba1c', 4, 1)->nullable();             // %

            // Lipid profile (mg/dL)
            $table->decimal('total_cholesterol', 5, 1)->nullable();
            $table->decimal('hdl', 5, 1)->nullable();
            $table->decimal('ldl', 5, 1)->nullable();
            $table->decimal('triglycerides', 6, 1)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'taken_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
