<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Tables for the "database" queue driver. failed_jobs already exists (Laravel 6
 * migration) but lacks the uuid column required by the "database-uuids" failer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        if (! Schema::hasColumn('failed_jobs', 'uuid')) {
            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->string('uuid')->nullable()->after('id');
            });

            DB::table('failed_jobs')->whereNull('uuid')->pluck('id')->each(
                fn ($id) => DB::table('failed_jobs')->where('id', $id)->update(['uuid' => (string) Str::uuid()])
            );

            Schema::table('failed_jobs', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
