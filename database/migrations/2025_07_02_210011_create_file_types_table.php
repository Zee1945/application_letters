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
        Schema::create('file_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Nama jadwal aplikasi
            $table->unsignedBigInteger('parent_id')->nullable(); // Nama jadwal aplikasi
            $table->string('code')->nullable(); // Nama jadwal aplikasi
            $table->integer('trans_type')->nullable(); // Nama jadwal aplikasi
            $table->unsignedBigInteger('signed_role_id')->nullable(); // Tanggal mulai jadwal
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)
            $table->timestamps();
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)


            $table->foreign('parent_id')->references('id')->on('file_types')->nullOnDelete();
            $table->foreign('signed_role_id')->references('id')->on('roles')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_types');
    }
};
