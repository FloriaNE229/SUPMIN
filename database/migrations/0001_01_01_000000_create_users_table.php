<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        Schema::create('users', function (Blueprint $table) {

            // UUID
            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | INFORMATIONS PERSONNELLES
            |--------------------------------------------------------------------------
            */

            $table->string('nom', 100);

            $table->string('prenom', 100);

            /*
            |--------------------------------------------------------------------------
            | AUTHENTIFICATION
            |--------------------------------------------------------------------------
            */

            $table->string('email')->unique();

            $table->timestamp('email_verified_at')->nullable();

            $table->string('mot_de_passe_hash');

            /*
            |--------------------------------------------------------------------------
            | CONTACT
            |--------------------------------------------------------------------------
            */

            $table->string('telephone', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('statut', [
                'actif',
                'suspendu',
                'desactive'
            ])->default('actif');

            /*
            |--------------------------------------------------------------------------
            | SECURITE
            |--------------------------------------------------------------------------
            */

            $table->timestamp('date_derniere_connexion')
                ->nullable();

            $table->smallInteger('tentatives_echec')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | LARAVEL
            |--------------------------------------------------------------------------
            */

            $table->rememberToken();

            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | PASSWORD RESET TOKENS
        |--------------------------------------------------------------------------
        */

        Schema::create('password_reset_tokens', function (Blueprint $table) {

            $table->string('email')->primary();

            $table->string('token');

            $table->timestamp('created_at')->nullable();
        });

        /*
        |--------------------------------------------------------------------------
        | SESSIONS
        |--------------------------------------------------------------------------
        */

        Schema::create('sessions', function (Blueprint $table) {

            $table->string('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | USER UUID FK
            |--------------------------------------------------------------------------
            */

            $table->uuid('user_id')
                ->nullable()
                ->index();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | SESSION DATA
            |--------------------------------------------------------------------------
            */

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->longText('payload');

            $table->integer('last_activity')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');

        Schema::dropIfExists('password_reset_tokens');

        Schema::dropIfExists('users');
    }
};