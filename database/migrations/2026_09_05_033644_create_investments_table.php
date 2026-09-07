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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_space_id')->constrained('couple_spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 100);
            $table->string('symbol', 30)->nullable();
            $table->string('asset_type', 30);
            $table->string('scope', 20)->default('personal');
            $table->decimal('quantity', total: 24, places: 8)->default(0);
            $table->decimal('average_buy_price', total: 20, places: 2)->default(0);
            $table->decimal('current_price', total: 20, places: 2)->default(0);
            $table->decimal('realized_profit_loss', total: 20, places: 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['couple_space_id', 'is_active']);
            $table->index(['couple_space_id', 'scope', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
