<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entites', function (Blueprint $table) {

            //  UUID PK
            $table->uuid('id')->primary();

            //  identification
            $table->string('code', 20)->unique();
            $table->string('denomination', 255);
            $table->string('sigle', 20)->nullable();

            //  type
            $table->enum('type_entite', [
                'structure_administrative',
                'agence',
                'programme'
            ]);

            // localisation
            $table->string('localisation', 255);
            $table->string('region', 100);

            //  responsable (USER → BIGINT)
            $table->foreignId('responsable_id')
                ->constrained('users')
                ->cascadeOnDelete();

            //  statut
            $table->enum('statut', [
                'actif',
                'suspendu',
                'cloture'
            ])->default('actif');

            //  date création
            $table->date('date_creation');

            //  hiérarchie (self relation UUID)
            $table->uuid('entite_parente_id')->nullable();

            $table->foreign('entite_parente_id')
                ->references('id')
                ->on('entites')
                ->nullOnDelete();

            // timestamps (très recommandé)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entites');
    }
};