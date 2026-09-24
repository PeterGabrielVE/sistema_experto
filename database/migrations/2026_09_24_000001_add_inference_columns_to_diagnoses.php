<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            // Category (rules.id) chosen by the inference engine or corrected by a doctor.
            $table->unsignedBigInteger('id_rule')->nullable();
            // ml | rules | manual
            $table->string('inference_source', 10)->nullable();
            $table->decimal('inference_confidence', 5, 4)->nullable();
            $table->string('model_version', 20)->nullable();

            $table->foreign('id_rule')->references('id')->on('rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            $table->dropForeign(['id_rule']);
            $table->dropColumn(['id_rule', 'inference_source', 'inference_confidence', 'model_version']);
        });
    }
};
