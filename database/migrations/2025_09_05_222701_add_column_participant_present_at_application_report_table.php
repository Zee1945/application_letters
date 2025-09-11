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
        Schema::table('application_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('total_participants')->nullable();
            $table->unsignedBigInteger('total_participants_not_present')->nullable();
            $table->unsignedBigInteger('total_participants_present')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('total_participants')->nullable();
            $table->unsignedBigInteger('total_participants_not_present')->nullable();
            $table->unsignedBigInteger('total_participants_present')->nullable();
        });
    }
};
