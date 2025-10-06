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
        Schema::create('application_reports', function (Blueprint $table) {
            $table->id();
            $table->text('introduction')->nullable(); // Kata pengantar
            $table->text('background')->nullable(); // Realisasi anggaran (input bukti bayar/SPJ)
            $table->text('activity_description')->nullable(); // Uraian pelaksanaan kegiatan
            $table->text('obstacles')->nullable(); // Kendala
            $table->text('conclusion')->nullable(); // Simpulan
            $table->text('recommendations')->nullable(); // Saran
            $table->text('closing')->nullable(); // Penutup
            $table->text('speaker_material')->nullable(); // Materi narasumber
            $table->text('speaker_cv')->nullable(); // CV narasumber
            $table->text('financial_statement')->nullable(); // SPJ Keuangan (optional, tergantung sudah diinput di realisasi anggaran)
            $table->text('photos')->nullable(); // Foto dokumentasi
            $table->string('tax_id_number')->nullable(); // NPWP
            $table->string('identity_card_number')->nullable(); // KTP

            $table->text('delete_note')->nullable(); // Catatan penghapusan
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh

            $table->timestamps(); // created_at dan updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_reports');
    }
};
