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
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', total: 24, places: 8);
            $table->decimal('unit_price', total: 20, places: 2);
            $table->decimal('gross_amount', total: 20, places: 2);
            $table->decimal('fee_amount', total: 15, places: 2)->default(0);
            $table->decimal('realized_profit_loss', total: 20, places: 2)->default(0);
            $table->timestamp('transaction_date');
            $table->string('client_reference', 64)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'client_reference'], 'investment_transactions_client_reference_unique');
            $table->index(['investment_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_transactions');
    }
};
