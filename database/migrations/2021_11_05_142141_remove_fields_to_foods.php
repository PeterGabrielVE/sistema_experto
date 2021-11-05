<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToFoods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('foods', function (Blueprint $table) {

            $table->dropColumn('meats_and_egg');
            $table->dropColumn('vegetables');
            $table->dropColumn('dairy_products');
            $table->dropColumn('milk');
            $table->dropColumn('vegetables');
            $table->dropColumn('fruits');
            $table->dropColumn('bread_and_cereals');
            $table->dropColumn('sugars');
            $table->dropColumn('oils_and_fats');
            $table->dropColumn('medium');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('foods', function (Blueprint $table) {
            //
        });
    }
}
