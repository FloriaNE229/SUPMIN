<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {

            //  UUID
            $table->uuid('id')->primary();

            //  section
            $table->uuid('section_id');

            $table->foreign('section_id')
                ->references('id')
                ->on('sections_formulaire')
                ->cascadeOnDelete();

            //  contenu
            $table->text('libelle');
            $table->text('description_aide')->nullable();

            //  type
            $table->enum('type_question', [
                'texte_court',
                'texte_long',
                'choix_unique',
                'choix_multiple',
                'liste',
                'note',
                'date',
                'fichier',
                'tableau'
            ]);

            //  options (choix)
            $table->json('options')->nullable();

            //  obligatoire
            $table->boolean('est_obligatoire')->default(true);

            //  logique conditionnelle
            $table->json('condition_affichage')->nullable();

            //  ordre
            $table->smallInteger('ordre');

            //  validation dynamique
            $table->json('validation_regles')->nullable();

            //  anti doublon ordre
            $table->unique(['section_id', 'ordre']);

            // timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};