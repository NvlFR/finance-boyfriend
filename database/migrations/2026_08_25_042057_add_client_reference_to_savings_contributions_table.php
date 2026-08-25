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
        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->string('client_reference')->nullable()->after('notes');
            $table->unique(
                ['user_id', 'client_reference'],
                'savings_contributions_client_reference_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_contributions', function (Blueprint $table) {
            $table->dropUnique('savings_contributions_client_reference_unique');
            $table->dropColumn('client_reference');
        });
    }
};
