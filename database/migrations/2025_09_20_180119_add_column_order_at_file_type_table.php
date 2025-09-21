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
        Schema::table('file_types', function (Blueprint $table) {
            $table->unsignedBigInteger('order')->default(0)->after('name');
        });
        Schema::table('application_files', function (Blueprint $table) {
            $table->unsignedBigInteger('order')->default(0)->after('display_name');
        });
     
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_types', function (Blueprint $table) {
            $table->dropColumn('order');
        });
        Schema::table('application_files', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
