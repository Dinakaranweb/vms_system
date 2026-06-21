<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->constrained('users')->after('created_by');
            }
            if (!Schema::hasColumn('tenders', 'is_owner_controlled')) {
                $table->boolean('is_owner_controlled')->default(true)->after('owner_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['owner_id', 'is_owner_controlled']);
        });
    }
};