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
        Schema::create('application_participants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama peserta
            $table->string('institution'); // Asal lembaga peserta
            $table->unsignedBigInteger('participant_type_id')->nullable(); // Email peserta
            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('participant_type_id')->references('id')->on('participant_types')->nullOnDelete();
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
        Schema::dropIfExists('application_participants');
    }
};
