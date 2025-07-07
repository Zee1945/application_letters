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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('activity_name'); // Nama kegiatan
            $table->integer('funding_source'); // Nama kegiatan
            $table->integer('approval_status');
            $table->integer('current_user_approval'); // Nama kegiatan
            $table->string('user_approval_ids'); // Nama kegiatan
            $table->text('note')->nullable(); // Nama kegiatan
            $table->integer('draft_step_saved')->default(1); // Nama kegiatan
            // $table->date('activity_dates')->nullable(); // Tanggal pelaksanaan
            // $table->date('activity_end_date')->nullable(); // Tanggal pelaksanaan
            // $table->string('activity_location')->nullable(); // Tempat pelaksanaan
            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)

            $table->timestamps(); // Waktu dibuat dan diperbarui (created_at, updated_at)
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)

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
        Schema::dropIfExists('applications');
    }
};
