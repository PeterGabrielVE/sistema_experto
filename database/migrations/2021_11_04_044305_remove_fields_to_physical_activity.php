<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsToPhysicalActivity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('physical_activity', function (Blueprint $table) {
            $table->dropColumn('repose');
            $table->dropColumn('light');
            $table->dropColumn('moderate');
            $table->dropColumn('intense');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('physical_activity', function (Blueprint $table) {
            $table->string('repose');
            $table->string('light');
            $table->string('moderate');
            $table->string('intense');
        });
    }
}
