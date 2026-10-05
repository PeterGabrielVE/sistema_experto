<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Macronutrient rules configured by the Doctor Jefe: if variable operator value,
 * apply the actions. Sent to the expert service with each evaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('macro_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('advice', 500)->nullable();
            $table->string('variable', 30); // config('clinical.configurable_macro_rules.variables')
            $table->string('operator', 2);
            $table->decimal('value', 10, 3);
            $table->json('actions'); // {glycemic_load: 80, carbohydrates: 45, ...}
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('macro_rules');
    }
};
