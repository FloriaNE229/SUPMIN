<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_user', function (Blueprint $table) {
            $table->uuid('mission_id');
            $table->uuid('user_id');

            $table->enum('role', [
                'leader',
                'membre'
            ])->default('membre');

            $table->timestamps();

            $table->primary(['mission_id', 'user_id']);

            $table->foreign('mission_id')
                ->references('id')
                ->on('missions')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_user');
    }
};