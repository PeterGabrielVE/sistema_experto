<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToDiagnoses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diagnoses', function (Blueprint $table) {

            $table->integer('id_patient');
    
            $table->decimal('carbohydrate')->nullable();
            $table->decimal('isocaloric')->nullable();
            $table->decimal('lipido')->nullable();
            $table->decimal('protein')->nullable();
            $table->decimal('imc_desired')->nullable();

            $table->decimal('result_harris')->nullable();
            $table->decimal('result_pulgar')->nullable();
            $table->decimal('insulin_index')->nullable();

            $table->decimal('size')->nullable();
            $table->decimal('weight')->nullable();
            $table->decimal('physical_activity')->nullable();

            $table->decimal('workday')->nullable();
            $table->decimal('schedule_meal')->nullable();
            $table->decimal('schedule_activity')->nullable();
            $table->decimal('imc')->nullable();

            $table->integer('age');
            
            $table->foreign('id_patient')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diagnoses');
    }
}
