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
        Schema::table('application_draft_cost_budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_cost_realization')->after('total')->nullable();
            $table->unsignedBigInteger('volume_realization')->after('total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
