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

    $table->uuid('id')->primary();

    // ⚠️ SPATIE UTILISE 'name'
    $table->string('name')->unique();

    $table->string('guard_name');

    // TES CHAMPS METIER
    $table->string('code')->unique();
    $table->string('libelle');
    $table->text('description')->nullable();
    $table->json('permissions')->nullable();

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
