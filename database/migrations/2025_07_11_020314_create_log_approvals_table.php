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
        Schema::create('log_approvals', function (Blueprint $table) {
            $table->id();
            $table->text('notes')->nullable(); // Catatan penghapusan (jika ada)
            $table->text('location_city')->nullable(); // Catatan penghapusan (jika ada)
            $table->string('action')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('trans_type')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('user_id')->nullable(); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)

            $table->foreign('application_id')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
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
        Schema::dropIfExists('log_approvals');
    }
};
