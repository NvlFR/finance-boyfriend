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
        Schema::table('couple_spaces', function (Blueprint $table) {
            $table->foreignId('birthday_surprise_manager_user_id')
                ->nullable()
                ->after('user_two_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couple_spaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('birthday_surprise_manager_user_id');
        });
    }
};
