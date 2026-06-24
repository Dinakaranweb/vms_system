<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any duplicate applications first (keep the earliest one per vendor+tender)
        $duplicates = DB::table('tender_applications')
            ->select('tender_id', 'vendor_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('tender_id', 'vendor_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            DB::table('tender_applications')
                ->where('tender_id', $dup->tender_id)
                ->where('vendor_id', $dup->vendor_id)
                ->where('id', '!=', $dup->keep_id)
                ->delete();
        }

        Schema::table('tender_applications', function (Blueprint $table) {
            $table->unique(['tender_id', 'vendor_id'], 'unique_vendor_tender_application');
        });
    }

    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            $table->dropUnique('unique_vendor_tender_application');
        });
    }
};
