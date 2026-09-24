<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinical record (ficha clínica): one per patient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('patients')->cascadeOnDelete();

            $table->text('consultation_reason');

            // Medical history
            $table->boolean('has_diabetes')->default(false);
            $table->boolean('has_prediabetes')->default(false);
            $table->boolean('has_hypertension')->default(false);
            $table->boolean('has_dyslipidemia')->default(false);
            $table->boolean('has_pcos')->default(false);
            $table->text('other_conditions')->nullable();
            $table->text('family_history')->nullable();
            $table->text('medications')->nullable();
            $table->text('food_allergies')->nullable();

            // Habits
            $table->string('smoking', 10)->nullable();
            $table->string('alcohol', 10)->nullable();
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->decimal('water_liters', 3, 1)->nullable();

            // Anthropometry
            $table->decimal('waist_cm', 5, 1)->nullable();

            // Laboratory
            $table->date('lab_date')->nullable();
            $table->decimal('fasting_glucose', 5, 1)->nullable();   // mg/dL
            $table->decimal('fasting_insulin', 5, 1)->nullable();   // µU/mL
            $table->decimal('hba1c', 4, 1)->nullable();             // %
            $table->decimal('total_cholesterol', 5, 1)->nullable(); // mg/dL
            $table->decimal('hdl', 5, 1)->nullable();
            $table->decimal('ldl', 5, 1)->nullable();
            $table->decimal('triglycerides', 6, 1)->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_records');
    }
};
