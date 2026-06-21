<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('tender_documents', 'order')) {
                $table->integer('order')->default(0)->after('version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tender_documents', function (Blueprint $table) {
            if (Schema::hasColumn('tender_documents', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};