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
            $table->boolean('merge_with_attachments')->default(false)->after('closing');
            $table->enum('merge_status', ['idle', 'pending', 'processing', 'done', 'failed'])
                  ->default('idle')->after('merge_with_attachments');
            $table->timestamp('merged_at')->nullable()->after('merge_status');
            $table->text('merge_error')->nullable()->after('merged_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_reports', function (Blueprint $table) {
            $table->dropColumn([
                'merge_with_attachments',
                'merge_status',
                'merged_at',
                'merge_error',
            ]);
        });
    }
};
