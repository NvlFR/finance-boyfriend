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
            $table->boolean('is_emergency_fund')->default(false)->after('scope');
            $table->index(['couple_space_id', 'is_emergency_fund']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('emergency_savings_goal_id')
                ->nullable()
                ->after('wallet_id')
                ->constrained('savings_goals')
                ->restrictOnDelete();
            $table->foreignId('wallet_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['emergency_savings_goal_id']);
            $table->dropColumn('emergency_savings_goal_id');
            $table->foreignId('wallet_id')->nullable(false)->change();
        });

        Schema::table('savings_goals', function (Blueprint $table) {
            $table->dropIndex(['couple_space_id', 'is_emergency_fund']);
            $table->dropColumn('is_emergency_fund');
        });
    }
};
