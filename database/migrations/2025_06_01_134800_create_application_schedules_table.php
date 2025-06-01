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
        Schema::create('application_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama jadwal aplikasi
            $table->dateTime('start_date'); // Tanggal mulai jadwal
            $table->dateTime('end_date'); // Tanggal mulai jadwal
            $table->string('moderator_ids'); // Nama jadwal aplikasi
            $table->string('speaker_ids'); // Nama jadwal aplikasi

            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)

            $table->timestamps();
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)


            $table->foreign('application_id')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_schedules');
    }
};
