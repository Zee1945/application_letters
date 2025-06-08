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
        Schema::create('application_user_approvals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable(); // Dibuat oleh (ID user)
            $table->text('user_text')->nullable(); // Dibuat oleh (ID user)
            $table->integer('sequence')->nullable();
            $table->integer('status')->default(0); // Status persetujuan (0: pending, 1: approved, 2: process 3: rejected)
            $table->text('note')->nullable(); // Catatan persetujuan (jika ada)

            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)
            $table->timestamps();
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)

            $table->foreign('application_id')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_approvals');
    }
};
