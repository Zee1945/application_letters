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
            Schema::table('application_files', function (Blueprint $table) {
                $table->unique(['file_type_id', 'application_id', 'department_id'], 'unique_application_file');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_files', function (Blueprint $table) {
            $table->dropUnique('unique_application_file');
        });
    }
};
