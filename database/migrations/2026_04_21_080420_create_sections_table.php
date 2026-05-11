<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {

            //  UUID PK
            $table->uuid('id')->primary();

            //  formulaire
            $table->uuid('formulaire_id');

            $table->foreign('formulaire_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();

            //  contenu
            $table->string('titre');
            $table->text('description')->nullable();

            //  ordre affichage
            $table->smallInteger('ordre');

            //  éviter doublon d’ordre dans un même formulaire
            $table->unique(['formulaire_id', 'ordre']);

            // timestamps (recommandé)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};