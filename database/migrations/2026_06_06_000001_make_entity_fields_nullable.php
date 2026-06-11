<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entites', function (Blueprint $table) {
            // Permettre responsable_id NULL
            $table->dropForeign(['responsable_id']);
            $table->uuid('responsable_id')->nullable()->change();
            $table->foreign('responsable_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Permettre date_creation NULL
            $table->date('date_creation')->nullable()->change();

            // Augmenter la taille du sigle pour les longs sigles (CPMI-NFED, PSILMNT)
            $table->string('sigle', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('entites', function (Blueprint $table) {
            $table->dropForeign(['responsable_id']);
            $table->uuid('responsable_id')->nullable(false)->change();
            $table->foreign('responsable_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->date('date_creation')->nullable(false)->change();
            $table->string('sigle', 20)->nullable()->change();
        });
    }
};