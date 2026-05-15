<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {

            // UUID PK
            $table->uuid('id')->primary();

            // Formulaire parent
            $table->uuid('form_id');

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();

            // Contenu
            $table->string('title');
            $table->text('description')->nullable();

            // Ordre d'affichage
            $table->smallInteger('order')->default(0);

            // Unicité de l'ordre dans un même formulaire
            $table->unique(['form_id', 'order']);

            // Timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};