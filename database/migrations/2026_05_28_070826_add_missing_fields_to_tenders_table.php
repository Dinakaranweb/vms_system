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
        // Add these columns
Schema::table('tenders', function (Blueprint $table) {
    $table->text('technical_requirements')->nullable();
    $table->text('financial_requirements')->nullable();
    $table->time('submission_time')->nullable();
    $table->time('opening_time')->nullable();
    $table->string('opening_venue')->nullable();
    $table->json('pre_bid_queries')->nullable();
    $table->json('corrigendum')->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            //
        });
    }
};
