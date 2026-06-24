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
        Schema::table('application_participants', function (Blueprint $table) {
            // Penanda apakah peserta ini menjadi opsi pada rundown (0 = tidak, 1 = ya).
            $table->boolean('is_option_rundown')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_participants', function (Blueprint $table) {
            $table->dropColumn('is_option_rundown');
        });
    }
};
