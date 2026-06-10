<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('compte_active')->default(false)->after('statut');
            $table->string('mdp_activation')->nullable()->after('compte_active');
            $table->integer('tentatives_activation')->default(0)->after('mdp_activation');
            $table->boolean('compte_bloque')->default(false)->after('tentatives_activation');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['compte_active', 'mdp_activation', 'tentatives_activation', 'compte_bloque']);
        });
    }
};