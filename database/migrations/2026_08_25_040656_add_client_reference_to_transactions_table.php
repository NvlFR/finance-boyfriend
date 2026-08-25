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
            $table->string('client_reference', 64)->nullable()->after('receipt_image_path');
            $table->unique(['couple_space_id', 'user_id', 'client_reference'], 'transactions_client_reference_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_client_reference_unique');
            $table->dropColumn('client_reference');
        });
    }
};
