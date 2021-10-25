<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToDiagnoses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diagnoses', function (Blueprint $table) {

            $table->foreignId('provider_id')->nullable();
            $table->foreignId('garage_id')->nullable();
            $table->foreignId('client_id')->nullable();

            $table->decimal('discount');
            $table->decimal('net_price');
            $table->integer('delivery')->nullable();
            $table->integer('shipping');
            
            $table->foreign('communication_id')->references('id')->on('communications')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diagnoses', function (Blueprint $table) {
            //
        });
    }
}
