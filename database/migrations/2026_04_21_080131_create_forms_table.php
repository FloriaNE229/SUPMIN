<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Clé primaire UUID
            |--------------------------------------------------------------------------
            */
            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | Référence métier
            |--------------------------------------------------------------------------
            */
            $table->string('code', 50)->unique();

            /*
            |--------------------------------------------------------------------------
            | Mission liée (nullable pour templates)
            |--------------------------------------------------------------------------
            */
            $table->uuid('mission_id')->nullable();

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Informations principales
            |--------------------------------------------------------------------------
            */
            $table->string('titre');
            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Schéma JSON complet du formulaire
            |--------------------------------------------------------------------------
            */
            $table->json('schema');

            /*
            |--------------------------------------------------------------------------
            | Template réutilisable
            |--------------------------------------------------------------------------
            */
            $table->boolean('est_modele')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Version
            |--------------------------------------------------------------------------
            */
            $table->unsignedSmallInteger('version')->default(1);

            /*
            |--------------------------------------------------------------------------
            | Statut
            |--------------------------------------------------------------------------
            */
            $table->enum('statut', [
                'brouillon',
                'publie',
                'archive'
            ])->default('brouillon');

            /*
            |--------------------------------------------------------------------------
            | Créateur
            |--------------------------------------------------------------------------
            */
            $table->uuid('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};