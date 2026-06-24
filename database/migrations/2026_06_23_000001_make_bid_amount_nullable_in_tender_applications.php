<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            $table->decimal('bid_amount', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            $table->decimal('bid_amount', 15, 2)->nullable(false)->change();
        });
    }
};
