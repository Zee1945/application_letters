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
            $table->unsignedBigInteger('merged_file_id')->nullable()->after('file_id');
            $table->foreign('merged_file_id')->references('id')->on('files')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_files', function (Blueprint $table) {
            $table->dropForeign(['merged_file_id']);
            $table->dropColumn([
                'merge_with_attachments',
                'merge_status',
                'merged_at',
                'merge_error',
            ]);
        });
    }
};
