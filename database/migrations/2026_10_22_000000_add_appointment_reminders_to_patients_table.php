<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consent to appointment reminders by e-mail (US-6.1, D-3): off by default, so existing
 * patients get none until the doctor checks it. Unsubscribing by the e-mail link turns
 * it off and keeps the date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('appointment_reminders')->default(false);
            $table->dateTime('reminders_unsubscribed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['appointment_reminders', 'reminders_unsubscribed_at']);
        });
    }
};
