<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->timestamp('review_opened_at')->nullable()->after('bid_opened_by');
            $table->foreignId('review_opened_by')->nullable()->after('review_opened_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['review_opened_by']);
            $table->dropColumn(['review_opened_at', 'review_opened_by']);
        });
    }
};
