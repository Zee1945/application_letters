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
         Schema::table('application_user_approvals', function (Blueprint $table) {
            $table->string('role'); 
            $table->string('role_text'); 
            $table->tinyInteger('trans_type'); 
            $table->tinyInteger('is_verificator'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('application_user_approvals');
    }
};
