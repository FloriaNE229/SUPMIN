<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {

            $table->uuid('id')->primary();

        

            $table->uuid('user_id')->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();


            $table->string('action');

        
            $table->string('model_type');

            $table->uuid('model_id');

           

            $table->json('old_values')->nullable();

            $table->json('new_values')->nullable();

        

            $table->ipAddress('ip_address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};