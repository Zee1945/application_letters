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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('updated_at'); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('position_id')->nullable()->after('department_id'); // Catatan penghapusan (jika ada)
            $table->text('delete_note')->nullable()->after('position_id'); // Catatan penghapusan (jika ada)
            $table->unsignedBigInteger('created_by')->nullable()->after('delete_note'); // Dibuat oleh (ID user)
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by'); // Diubah oleh (ID user)
            $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by'); // Dihapus oleh (ID user)
            $table->softDeletes(); // Waktu penghapusan lembut (deleted_at)

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
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
