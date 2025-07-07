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
        Schema::create('application_details', function (Blueprint $table) {
            $table->id();
            $table->text('activity_outcome')->nullable(); // Hasil/output kegiatan
            $table->text('activity_output')->nullable(); // Keluaran kegiatan
            $table->string('performance_indicator')->nullable(); // Indikator kinerja kegiatan
            $table->string('unit_of_measurment')->nullable(); // Indikator kinerja kegiatan
            $table->string('activity_volume')->nullable(); // Volume kegiatan
            $table->text('general_description')->nullable(); // Gambaran umum
            $table->text('objectives')->nullable(); // Maksud dan tujuan
            $table->text('beneficiaries')->nullable(); // Penerima manfaat
            $table->text('activity_scope')->nullable(); // Ruang lingkup kegiatan
            $table->text('implementation_method')->nullable(); // Metode pelaksanaan
            $table->text('implementation_stages')->nullable(); // Tahapan pelaksanaan
            $table->string('activity_dates')->nullable(); // Tanggal pelaksanaan
            $table->string('activity_location')->nullable(); // Tempat pelaksanaan

            // $table->string('speaker_name')->nullable(); // Nama narasumber
            // $table->string('speaker_institution')->nullable(); // Asal lembaga narasumber
            // $table->string('moderator_name')->nullable(); // Nama moderator
            // $table->string('moderator_institution')->nullable(); // Asal lembaga moderator
            // $table->json('participants')->nullable(); // Peserta (Nama, asal lembaga, tabel)
            // $table->text('activity_schedule')->nullable(); // Jadwal kegiatan
            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)

            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)
            $table->unsignedBigInteger('application_id')->nullable(); // ID aplikasi terkait
            $table->timestamps(); // Waktu dibuat dan diperbarui (created_at, updated_at)
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
        Schema::dropIfExists('application_details');
    }
};
