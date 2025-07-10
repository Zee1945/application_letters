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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->text('encrypted_filename')->nullable();
            $table->string('mimetype')->nullable();
            $table->string('belongs_to')->nullable();
            $table->string('path')->nullable();
            $table->string('storage_type')->nullable();
            $table->bigInteger('filesize')->nullable();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->text('deleted_note')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
