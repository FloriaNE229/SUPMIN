<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entites', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | UUID PK
            |--------------------------------------------------------------------------
            */

            $table->uuid('id')->primary();

            /*
            |--------------------------------------------------------------------------
            | IDENTIFICATION
            |--------------------------------------------------------------------------
            */

            $table->string('code', 20)->unique();

            $table->string('denomination', 255);

            $table->string('sigle', 20)->nullable();

            /*
            |--------------------------------------------------------------------------
            | TYPE
            |--------------------------------------------------------------------------
            */

            $table->enum('type_entite', [
                'structure_administrative',
                'agence',
                'programme'
            ]);

            /*
            |--------------------------------------------------------------------------
            | LOCALISATION
            |--------------------------------------------------------------------------
            */

            $table->string('localisation', 255);

            $table->string('region', 100);

            /*
            |--------------------------------------------------------------------------
            | RESPONSABLE (UUID FK → users.id)
            |--------------------------------------------------------------------------
            */

            $table->uuid('responsable_id');

            $table->foreign('responsable_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | STATUT
            |--------------------------------------------------------------------------
            */

            $table->enum('statut', [
                'actif',
                'suspendu',
                'cloture'
            ])->default('actif');

            /*
            |--------------------------------------------------------------------------
            | DATE CREATION
            |--------------------------------------------------------------------------
            */

            $table->date('date_creation');

            /*
            |--------------------------------------------------------------------------
            | HIERARCHIE
            |--------------------------------------------------------------------------
            */

            $table->uuid('entite_parente_id')
                ->nullable();

            $table->foreign('entite_parente_id')
                ->references('id')
                ->on('entites')
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
        Schema::dropIfExists('entites');
    }
};