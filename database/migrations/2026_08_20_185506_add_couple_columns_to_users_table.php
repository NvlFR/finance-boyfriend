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
            $table->string('nickname', 50)->nullable()->after('name');
            $table->string('avatar_url', 255)->nullable()->after('email');
            $table->string('theme_color', 20)->default('#6366F1')->after('avatar_url'); // Hex code for couple color
            $table->foreignId('current_couple_space_id')->nullable()->after('theme_color')->constrained('couple_spaces')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_couple_space_id']);
            $table->dropColumn(['nickname', 'avatar_url', 'theme_color', 'current_couple_space_id']);
        });
    }
};
