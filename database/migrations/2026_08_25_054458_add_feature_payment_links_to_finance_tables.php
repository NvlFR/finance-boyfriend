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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('source_type', 30)->nullable()->after('client_reference');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->index(['source_type', 'source_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dateTime('last_paid_at')->nullable()->after('next_billing_date');
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->foreignId('transaction_id')->nullable()->after('to_user_id')->constrained('transactions')->nullOnDelete();
            $table->string('client_reference', 64)->nullable()->after('transaction_id');
            $table->unique(['couple_space_id', 'from_user_id', 'client_reference'], 'settlements_space_user_reference_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropUnique('settlements_space_user_reference_unique');
            $table->dropConstrainedForeignId('transaction_id');
            $table->dropColumn('client_reference');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('last_paid_at');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
