<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToFoodGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('food_group', function (Blueprint $table) {
            $table->dropColumn('fruits');
            $table->dropColumn('vegetables');
            $table->dropColumn('cereals');
            $table->dropColumn('protein');
            $table->dropColumn('others');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('food_group', function (Blueprint $table) {
            $table->string('fruits');
            $table->string('vegetables');
            $table->string('cereals');
            $table->string('protein');
            $table->string('others');
        });
    }
}
