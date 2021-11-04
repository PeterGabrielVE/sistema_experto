<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodGroupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('food_group', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fruits');
            $table->string('vegetables');
            $table->string('cereals');
            $table->string('protein');
            $table->string('others');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('food_group');
    }
}
