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
        Schema::create('log_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity'); // Nama aktivitas/log
            $table->text('description')->nullable(); // Deskripsi aktivitas
            $table->unsignedBigInteger('user_id')->nullable(); // User yang melakukan aktivitas
            $table->unsignedBigInteger('reference_id')->nullable(); // Relasi ke aplikasi (jika ada)
            // $table->string('ip_address', 45)->nullable(); // IP address user
            // $table->string('user_agent')->nullable(); // User agent browser
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_activities');
    }
};
