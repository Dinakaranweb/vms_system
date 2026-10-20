<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->timestamp('bid_opening_at')->nullable()->after('closed_reason');
            $table->foreignId('bid_opened_by')->nullable()->after('bid_opening_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['bid_opened_by']);
            $table->dropColumn(['bid_opening_at', 'bid_opened_by']);
        });
    }
};
