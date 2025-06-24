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
        Schema::create('application_letter_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('letter_name')->nullable(); // Nama jadwal aplikasi
            $table->string('letter_label')->nullable(); // Nama jadwal aplikasi
            $table->string('type_field')->nullable(); // Nama jadwal aplikasi
            $table->string('letter_number')->nullable(); // Tanggal mulai jadwal
            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->unsignedBigInteger('department_id')->nullable(); // ID departemen
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
        Schema::dropIfExists('application_letter_numbers');
    }
};
