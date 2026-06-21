<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_applications', 'documents')) {
                $table->json('documents')->nullable()->after('financial_proposal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            if (Schema::hasColumn('tender_applications', 'documents')) {
                $table->dropColumn('documents');
            }
        });
    }
};