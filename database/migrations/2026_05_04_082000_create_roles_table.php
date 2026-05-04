<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {

            //  UUID PK
            $table->uuid('id')->primary();
            
            $table->uuid('role_id');

            $table->foreign('role_id')
               ->references('id')
               ->on('roles')
               ->cascadeOnDelete();

            //  code métier
            $table->string('code', 50)->unique();

            //  affichage
            $table->string('libelle', 100);
            $table->text('description')->nullable();

            //  permissions 
            $table->json('permissions');

            //  timestamps 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};