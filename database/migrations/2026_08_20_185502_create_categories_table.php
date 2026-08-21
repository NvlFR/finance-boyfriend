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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('couple_space_id')->nullable()->constrained('couple_spaces')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20)->default('expense'); // income, expense
            $table->string('icon', 50)->default('tag');
            $table->string('color', 20)->default('#64748B');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
