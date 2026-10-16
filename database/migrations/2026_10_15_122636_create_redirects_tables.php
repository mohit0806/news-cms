<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Shared schema: rows are written by P2 tag merges (P2-T01.3); the admin
     * UI and public resolver land in P3-T05, which consumes these tables.
     */
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();
            $table->string('to_url');
            $table->smallInteger('status_code')->default(301);
            $table->boolean('is_wildcard')->default(false);
            $table->unsignedBigInteger('hits')->default(0);
            $table->dateTime('last_hit_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('not_found_log', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->unsignedBigInteger('hits')->default(0);
            $table->dateTime('last_seen_at')->nullable();
            $table->string('last_referrer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('not_found_log');
        Schema::dropIfExists('redirects');
    }
};
