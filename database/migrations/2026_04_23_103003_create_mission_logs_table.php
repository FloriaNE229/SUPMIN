<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_logs', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UUID PK
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | MISSION FK
            |--------------------------------------------------------------------------
            */

            $table->uuid('mission_id');

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | USER FK (UUID)
            |--------------------------------------------------------------------------
            */

            $table->uuid('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ACTION
            |--------------------------------------------------------------------------
            */

            $table->string('action');

            /*
            |--------------------------------------------------------------------------
            | CHANGES
            |--------------------------------------------------------------------------
            */

            $table->json('changes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMPS
            |--------------------------------------------------------------------------
            */

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_logs');
    }
};