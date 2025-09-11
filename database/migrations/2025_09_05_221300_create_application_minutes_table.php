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
        Schema::create('application_minutes', function (Blueprint $table) {
            $table->id();
            $table->text('topic');
            $table->text('explanation')->nullable();
            $table->text('follow_up')->nullable();
            $table->date('deadline')->nullable();
            $table->string('assignee')->nullable();


            $table->unsignedBigInteger('department_id')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->text('delete_note')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable(); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable(); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable(); // Dihapus oleh (ID user)

            $table->timestamps();
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_minutes');
    }
};
