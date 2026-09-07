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
        Schema::table('savings_goals', function (Blueprint $table) {
            $table->string('scope', 20)->default('shared')->after('created_by_user_id');
            $table->index(['couple_space_id', 'scope', 'created_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_goals', function (Blueprint $table) {
            $table->dropIndex(['couple_space_id', 'scope', 'created_by_user_id']);
            $table->dropColumn('scope');
        });
    }
};
