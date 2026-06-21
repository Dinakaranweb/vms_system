<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number')->unique();
            $table->foreignId('tender_id')->constrained();
            $table->foreignId('vendor_id')->constrained();
            $table->decimal('bid_amount', 15, 2);
            $table->enum('status', ['submitted', 'shortlisted', 'rejected', 'awarded'])->default('submitted');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_applications');
    }
};