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
            $table->string('dashboard_cover_path')->nullable()->after('anniversary_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couple_spaces', function (Blueprint $table) {
            $table->dropColumn('dashboard_cover_path');
        });
    }
};
