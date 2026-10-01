<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinical measurements (registro de mediciones): many per patient, one per control.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('measured_at');

            // Anthropometry
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('height_cm', 4, 1)->nullable();
            $table->decimal('waist_cm', 5, 1)->nullable();
            $table->decimal('hip_cm', 5, 1)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();

            // Vital signs
            $table->unsignedSmallInteger('systolic_bp')->nullable();  // mmHg
            $table->unsignedSmallInteger('diastolic_bp')->nullable(); // mmHg
            $table->unsignedSmallInteger('heart_rate')->nullable();   // lpm

            // Point-of-care test
            $table->decimal('capillary_glucose', 5, 1)->nullable();   // mg/dL

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_measurements');
    }
};
