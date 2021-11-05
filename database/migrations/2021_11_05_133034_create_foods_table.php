<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFoodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->bigIncrements('id_group');
            $table->string('item');
            $table->string('portion');
            $table->string('kcal');
            $table->string('protein');
            $table->string('lipid');
            $table->string('cho');
            $table->string('clna_mg');
            $table->string('k_mg');
            $table->string('p_mg');
            $table->string('ca_mg');
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
        Schema::dropIfExists('foods');
    }
}
