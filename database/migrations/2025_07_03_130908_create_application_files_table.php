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
        Schema::create('application_files', function (Blueprint $table) {
            $table->id();
            $table->string('type_name');
            $table->string('code');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->unsignedBigInteger('trans_type')->nullable();
            $table->unsignedBigInteger('status_ready')->default(0);
            $table->unsignedBigInteger('application_id')->nullable(); // Email peserta
            $table->unsignedBigInteger('department_id')->nullable(); // ID departemen
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('application_id')->references('id')->on('applications')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_files');
    }
};
