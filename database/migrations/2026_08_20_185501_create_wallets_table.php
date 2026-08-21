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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_space_id')->constrained('couple_spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete(); // null = joint wallet
            $table->string('name', 100);
            $table->string('type', 20)->default('personal'); // personal, joint
            $table->string('wallet_type', 30)->default('bank'); // bank, ewallet, cash, investment, credit_card
            $table->string('account_number', 50)->nullable();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('IDR');
            $table->string('color', 20)->default('#6366F1');
            $table->string('icon', 50)->default('wallet');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
