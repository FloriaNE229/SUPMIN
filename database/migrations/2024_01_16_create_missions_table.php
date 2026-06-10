<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UUID PK
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | REFERENCE METIER
            |--------------------------------------------------------------------------
            */

            $table->string('reference', 50)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | ENTITE (UUID FK)
            |--------------------------------------------------------------------------
            */

            $table->uuid('entity_id');

            $table->foreign('entity_id')
                ->references('id')
                ->on('entites')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | COORDINATEUR (UUID FK → users.id)
            |--------------------------------------------------------------------------
            */

            $table->uuid('coordinateur_id');

            $table->foreign('coordinateur_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | OBJECTIF
            |--------------------------------------------------------------------------
            */

            $table->text('objectif');

            /*
            |--------------------------------------------------------------------------
            | AXES PRIORITAIRES
            |--------------------------------------------------------------------------
            */

            $table->json('axes_prioritaires')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | DATES
            |--------------------------------------------------------------------------
            */

            $table->date('date_debut');

            $table->date('date_fin_prevue');

            $table->date('date_fin_effective')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('statut', [
                'planifiee',
                'en_cours',
                'suspendue',
                'cloturee'
            ])->default('planifiee');

            /*
            |--------------------------------------------------------------------------
            | ANNEE SUPERVISION
            |--------------------------------------------------------------------------
            */

            $table->smallInteger('annee_supervision');

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
        Schema::dropIfExists('missions');
    }
};