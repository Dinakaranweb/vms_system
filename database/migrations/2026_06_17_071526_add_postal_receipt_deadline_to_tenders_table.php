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
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'postal_receipt_deadline')) {
                $table->date('postal_receipt_deadline')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (Schema::hasColumn('tenders', 'postal_receipt_deadline')) {
                $table->dropColumn('postal_receipt_deadline');
            }
        });
    }
};
