<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToTimeOfDay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_of_day', function (Blueprint $table) {
            $table->dropColumn('breakfast');
            $table->dropColumn('morning_snack');
            $table->dropColumn('lunch');
            $table->dropColumn('evening_collation');
            $table->dropColumn('dinner');
            $table->dropColumn('night_collation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_of_day', function (Blueprint $table) {
            $table->string('breakfast');
            $table->string('morning_snack');
            $table->string('lunch');
            $table->string('evening_collation');
            $table->string('dinner');
            $table->string('night_collation');
        });
    }
}
