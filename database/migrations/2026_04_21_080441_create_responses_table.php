<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responses', function (Blueprint $table) {

            //  UUID
            $table->uuid('id')->primary();

            //  question
            $table->uuid('question_id');
            $table->foreign('question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();

            //  mission
            $table->uuid('mission_id');
            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->cascadeOnDelete();

            //  agent (USER → BIGINT)
            $table->foreignId('agent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            //  valeurs
            $table->text('valeur_texte')->nullable();
            $table->json('valeur_json')->nullable();

            //  fichiers
            $table->json('fichiers_joints')->nullable();

            //  géolocalisation
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();

            // ⏱ soumission
            $table->timestamp('submitted_at');

            //  mode
            $table->enum('mode_collecte', ['online', 'offline'])
                ->default('online');

            //  anti doublon (clé métier)
            $table->unique(['mission_id', 'question_id', 'agent_id']);

            // timestamps Laravel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};