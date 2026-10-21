<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Next appointment of a patient (US-6.1) and the state of its reminder e-mail.
 * At most one scheduled appointment per patient (AppointmentService::schedule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // professional in charge
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete(); // consultation where it was set
            $table->dateTime('scheduled_at'); // UTC
            $table->string('notes', 500)->nullable(); // preparation instructions, sent to the patient
            $table->string('status', 16); // scheduled | cancelled
            $table->dateTime('cancelled_at')->nullable();

            // Reminder: null pending | queued | sent | skipped | failed
            $table->string('reminder_status', 16)->nullable();
            $table->string('reminder_reason', 32)->nullable();
            $table->dateTime('reminder_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
