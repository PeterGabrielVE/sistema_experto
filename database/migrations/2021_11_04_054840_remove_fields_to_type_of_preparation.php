<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToTypeOfPreparation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('type_of_preparation', function (Blueprint $table) {
            $table->dropColumn('drink');
            $table->dropColumn('saucer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('type_of_preparation', function (Blueprint $table) {
            $table->string('drink');
            $table->string('saucer');
        });
    }
}
