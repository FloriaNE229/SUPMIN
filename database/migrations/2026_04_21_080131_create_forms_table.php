<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {

            //  UUID
            $table->uuid('id')->primary();

            //  mission (nullable pour template)
            $table->uuid('mission_id')->nullable();

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->nullOnDelete();

            //  contenu
            $table->string('titre');
            $table->text('description')->nullable();

            //  template
            $table->boolean('est_modele')->default(false);

            //  version
            $table->smallInteger('version')->default(1);

            //  statut (sans accent)
            $table->enum('statut', [
                'brouillon',
                'publie',
                'archive'
            ])->default('brouillon');

            //  créateur (BIGINT)
            $table->uuid('user_id');

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
                ->cascadeOnDelete();

            // timestamps
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};