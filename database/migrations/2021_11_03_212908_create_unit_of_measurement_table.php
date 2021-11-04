<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitOfMeasurementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_of_measurement', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('part');
            $table->string('gr');
            $table->string('kg');
            $table->string('ml');
            $table->string('l');
            $table->string('teaspoonful');
            $table->string('tablespoon');
            $table->string('oz');
            $table->string('lb');
            $table->string('bowl');
            $table->string('pinch');
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
        Schema::dropIfExists('unit_of_measurement');
    }
}
