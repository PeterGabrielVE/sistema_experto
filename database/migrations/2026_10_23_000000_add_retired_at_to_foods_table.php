<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A food retired from the catalog (US-4.2/AC-4) is no longer offered to the meal plan
 * generator nor the editor, but it is not deleted: saved menus refer to it by id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dateTime('retired_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            $table->dropColumn('retired_at');
        });
    }
};
