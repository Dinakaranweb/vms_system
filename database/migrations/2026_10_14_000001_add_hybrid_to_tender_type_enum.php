<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a third submission mode: 'hybrid' — a tender that accepts BOTH an online submission
 * through the portal and physical documents by post. Online and postal stay as they were;
 * hybrid simply satisfies both code paths (see Tender::hasOnlineBids() / hasPostalBids()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tenders', 'tender_type')) {
            return;
        }

        DB::statement("ALTER TABLE `tenders` MODIFY `tender_type` ENUM('online', 'postal', 'hybrid') NOT NULL DEFAULT 'online'");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tenders', 'tender_type')) {
            return;
        }

        // Hybrid tenders fall back to 'postal' — the stricter of the two modes, so no physical
        // submission silently stops being expected.
        DB::table('tenders')->where('tender_type', 'hybrid')->update(['tender_type' => 'postal']);

        DB::statement("ALTER TABLE `tenders` MODIFY `tender_type` ENUM('online', 'postal') NOT NULL DEFAULT 'online'");
    }
};
