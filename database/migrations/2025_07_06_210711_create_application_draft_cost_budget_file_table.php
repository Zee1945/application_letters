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
        Schema::create('draft_cost_budget_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_draft_cost_budget_id');
            $table->unsignedBigInteger('file_id');
            $table->timestamps();

            // Definisikan foreign key
            $table->foreign('application_draft_cost_budget_id')->references('id')->on('application_draft_cost_budgets')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }
    // php artisan migrate --path=/database/migrations/2025_07_06_210711_create_application_draft_cost_budget_file_table.php


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_cost_budget_files');
    }
};
