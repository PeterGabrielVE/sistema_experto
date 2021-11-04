<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToGender extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gender', function (Blueprint $table) {
            $table->dropColumn('femenine');
            $table->dropColumn('male');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gender', function (Blueprint $table) {
            $table->string('femenine');
            $table->string('male');
        });
    }
}
