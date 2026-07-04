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
            $table->unsignedBigInteger('group_file_type_id')->nullable()->after('parent_id');
            $table->foreign('group_file_type_id')->references('id')->on('group_file_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_types', function (Blueprint $table) {
            $table->dropForeign(['group_file_type_id']);
            $table->dropColumn('group_file_type_id');
        });
    }
};
