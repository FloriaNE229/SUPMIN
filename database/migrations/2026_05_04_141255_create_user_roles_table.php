<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {

            //  clé primaire 
            $table->uuid('id')->primary();

            //  user 
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            //  role 
            $table->uuid('role_id');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();

            //  date attribution
            $table->date('date_attribution');

            //  qui a attribué
            $table->foreignId('attribue_par')
                ->constrained('users')
                ->cascadeOnDelete();

            //  éviter doublons
            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};