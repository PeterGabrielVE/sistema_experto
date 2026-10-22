<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invitations to the patient portal (US-6.3). Only the SHA-256 of the token is stored: the token
 * itself travels in the welcome mail. A new invitation revokes the previous ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->char('token_hash', 64)->nullable()->unique(); // set when the mail is sent
            $table->dateTime('expires_at');
            $table->string('status', 16); // queued | sent | failed | accepted | revoked
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_invitations');
    }
};
