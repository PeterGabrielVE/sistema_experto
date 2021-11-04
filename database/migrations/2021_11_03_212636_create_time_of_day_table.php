<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTimeOfDayTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_of_day', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('breakfast');
            $table->string('morning_snack');
            $table->string('lunch');
            $table->string('evening_collation');
            $table->string('dinner');
            $table->string('night_collation');
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
        Schema::dropIfExists('time_of_day');
    }
}
