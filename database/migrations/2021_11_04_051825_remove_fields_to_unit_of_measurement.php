<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToUnitOfMeasurement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unit_of_measurement', function (Blueprint $table) {
            $table->dropColumn('part');
            $table->dropColumn('gr');
            $table->dropColumn('kg');
            $table->dropColumn('ml');
            $table->dropColumn('l');
            $table->dropColumn('teaspoonful');
            $table->dropColumn('tablespoon');
            $table->dropColumn('oz');
            $table->dropColumn('lb');
            $table->dropColumn('bowl');
            $table->dropColumn('pinch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_of_measurement', function (Blueprint $table) {
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
        });
    }
}
