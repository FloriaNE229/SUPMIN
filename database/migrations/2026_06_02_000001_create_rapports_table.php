<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('mission_id')->unique();
            $table->foreign('mission_id')->references('id')->on('missions')->onDelete('cascade');
            $table->string('reference', 50)->unique();
            $table->string('titre', 255);
            $table->text('synthese')->nullable();
            $table->enum('statut', ['brouillon', 'soumis', 'validé', 'transmis'])->default('brouillon');
            $table->string('url_pdf', 512)->nullable();
            $table->uuid('validee_par')->nullable();
            $table->foreign('validee_par')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->timestamp('date_transmission')->nullable();
            $table->timestamp('accuse_reception_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapports');
    }
};
