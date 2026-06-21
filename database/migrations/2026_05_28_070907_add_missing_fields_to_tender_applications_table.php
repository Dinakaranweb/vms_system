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
Schema::table('tender_applications', function (Blueprint $table) {
    $table->string('emda_reference_number')->nullable();
    $table->decimal('emda_paid_amount', 15, 2)->nullable();
    $table->text('technical_proposal')->nullable();
    $table->text('financial_proposal')->nullable();
    $table->json('evaluation_scores')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->foreignId('submitted_by')->nullable()->constrained('users');
    $table->timestamp('submitted_at')->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tender_applications', function (Blueprint $table) {
            //
        });
    }
};
