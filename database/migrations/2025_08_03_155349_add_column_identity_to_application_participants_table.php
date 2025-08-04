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
        Schema::table('application_participants', function (Blueprint $table) {
            $table->string('nip')->after('institution')->nullable();
            $table->string('rank')->after('nip')->nullable();
            $table->string('functional_position')->after('rank')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_participants', function (Blueprint $table) {
            $table->string('nip')->after('institution')->nullable();
            $table->string('rank')->after('nip')->nullable();
            $table->string('functional_position')->after('rank')->nullable();
        });
    }
};
