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
        Schema::create('birthday_surprises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_space_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('creator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('opening_message', 300);
            $table->text('appreciation_message');
            $table->text('love_letter');
            $table->text('closing_message');
            $table->json('photos')->nullable();
            $table->json('vouchers')->nullable();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();

            $table->index(['recipient_user_id', 'is_enabled', 'starts_at', 'ends_at'], 'birthday_surprises_active_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birthday_surprises');
    }
};
