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
        Schema::table('application_participants',function (Blueprint $table) {
            $table->unsignedBigInteger('idcard_file_id')->after('participant_type_id')->nullable();
            $table->unsignedBigInteger('npwp_file_id')->after('idcard_file_id')->nullable();
            $table->unsignedBigInteger('cv_file_id')->after('npwp_file_id')->nullable();

            $table->foreign('idcard_file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('npwp_file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('cv_file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_participants', function (Blueprint $table) {
            // Menghapus kolom dan foreign key yang ditambahkan
            $table->dropForeign(['idcard_file_id']);
            $table->dropForeign(['npwp_file_id']);
            $table->dropForeign(['cv_file_id']);
            $table->dropColumn(['idcard_file_id', 'npwp_file_id', 'cv_file_id']);
        });
    }
};
