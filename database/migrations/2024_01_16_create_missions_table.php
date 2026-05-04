<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {

            //  UUID PK
            $table->uuid('id')->primary();

            //  référence métier
            $table->string('reference', 50)->unique();

            //  entité (UUID)
            $table->uuid('entite_id');

            $table->foreign('entite_id')
                ->references('id')
                ->on('entites')
                ->cascadeOnDelete();

            //  coordinateur (USER → BIGINT)
            $table->foreignId('coordinateur_id')
                ->constrained('users')
                ->cascadeOnDelete();

            //  objectif
            $table->text('objectif');

            //  axes (JSON MySQL)
            $table->json('axes_prioritaires')->nullable();

            //  dates
            $table->date('date_debut');
            $table->date('date_fin_prevue');
            $table->date('date_fin_effective')->nullable();

            // statut (sans accent)
            $table->enum('statut', [
                'planifiee',
                'en_cours',
                'suspendue',
                'cloturee'
            ])->default('planifiee');

            //  année
            $table->smallInteger('annee_supervision');

            // timestamps Laravel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};