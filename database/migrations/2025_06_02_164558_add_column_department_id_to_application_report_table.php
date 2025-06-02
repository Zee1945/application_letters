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
            $table->unsignedBigInteger('application_id')->nullable()->after('identity_card_number'); // Email peserta
            $table->unsignedBigInteger('department_id')->nullable()->after('application_id'); // ID departemen
            $table->foreign('application_id')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_reports', function (Blueprint $table) {
            //
        });
    }
};
