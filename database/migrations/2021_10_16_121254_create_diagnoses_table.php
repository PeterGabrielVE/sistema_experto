<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiagnosesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('id_patient');
    
            $table->decimal('carbohydrate')->nullable();
            $table->decimal('isocaloric_carbohydrate')->nullable();
            $table->decimal('lipido')->nullable();
            $table->decimal('isocaloric_lipido')->nullable();
            $table->decimal('protein')->nullable();
            $table->decimal('isocaloric_protein')->nullable();
            $table->decimal('imc_desired')->nullable();

            $table->decimal('result_pulgar')->nullable();
            $table->decimal('insulin_index')->nullable();

            $table->decimal('size')->nullable();
            $table->decimal('weight')->nullable();
            $table->decimal('physical_activity')->nullable();

            $table->decimal('imc')->nullable();
            $table->integer('age');
            
            $table->foreign('id_patient')->references('id')->on('patients')->onDelete('cascade');
            $table->timestamps();
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
