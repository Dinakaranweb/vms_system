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
            if (!Schema::hasColumn('tenders', 'is_private')) {
                $table->boolean('is_private')->default(false)->after('tender_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (Schema::hasColumn('tenders', 'is_private')) {
                $table->dropColumn('is_private');
            }
        });
    }
};
