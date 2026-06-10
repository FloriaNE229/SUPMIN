<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommandations', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UUID PK
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | MISSION
            |--------------------------------------------------------------------------
            */

            $table->uuid('mission_id');

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | QUESTION
            |--------------------------------------------------------------------------
            */

            $table->uuid('question_id')
                ->nullable();

            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | REFERENCE
            |--------------------------------------------------------------------------
            */

            $table->string('reference', 50)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | CONTENU
            |--------------------------------------------------------------------------
            */

            $table->string('intitule');

            $table->text('description');

            /*
            |--------------------------------------------------------------------------
            | PRIORITE
            |--------------------------------------------------------------------------
            */

            $table->enum('priorite', [
                'critique',
                'majeur',
                'mineur'
            ]);

            /*
            |--------------------------------------------------------------------------
            | RESPONSABLE (UUID FK)
            |--------------------------------------------------------------------------
            */

            $table->uuid('responsable_id');

            $table->foreign('responsable_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | DELAI
            |--------------------------------------------------------------------------
            */

            $table->date('delai_realisation');

            /*
            |--------------------------------------------------------------------------
            | STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('statut', [
                'formulee',
                'transmise',
                'en_cours',
                'mise_en_oeuvre',
                'cloturee',
                'reportee',
                'non_mise_en_oeuvre'
            ])->default('formulee');

            /*
            |--------------------------------------------------------------------------
            | SUIVI
            |--------------------------------------------------------------------------
            */

            $table->smallInteger('nb_reports')
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | AUTO-REFERENCE
            |--------------------------------------------------------------------------
            */

            $table->uuid('recommandation_parente_id')
                ->nullable();

            $table->foreign('recommandation_parente_id')
                ->references('id')
                ->on('recommandations')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */

            $table->uuid('creee_par');

            $table->foreign('creee_par')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->uuid('validee_par')
                ->nullable();

            $table->foreign('validee_par')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

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
        Schema::dropIfExists('recommandations');
    }
};