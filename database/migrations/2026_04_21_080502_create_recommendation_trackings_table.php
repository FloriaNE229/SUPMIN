<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suivi_recommandations', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UUID PK
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | RECOMMANDATION
            |--------------------------------------------------------------------------
            */

            $table->uuid('recommandation_id');

            $table->foreign('recommandation_id')
                ->references('id')
                ->on('recommandations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | ANCIEN STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('ancien_statut', [
                'formulee',
                'transmise',
                'en_cours',
                'mise_en_oeuvre',
                'cloturee',
                'reportee',
                'non_mise_en_oeuvre'
            ]);

            /*
            |--------------------------------------------------------------------------
            | NOUVEAU STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('nouveau_statut', [
                'formulee',
                'transmise',
                'en_cours',
                'mise_en_oeuvre',
                'cloturee',
                'reportee',
                'non_mise_en_oeuvre'
            ]);

            /*
            |--------------------------------------------------------------------------
            | COMMENTAIRE
            |--------------------------------------------------------------------------
            */

            $table->text('commentaire')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | PREUVES
            |--------------------------------------------------------------------------
            */

            $table->json('preuves_jointes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | UPDATED BY (UUID FK)
            |--------------------------------------------------------------------------
            */

            $table->uuid('updated_by');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | TIMESTAMP EXACT
            |--------------------------------------------------------------------------
            */

            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suivi_recommandations');
    }
};