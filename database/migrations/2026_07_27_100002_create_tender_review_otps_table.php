<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_review_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->onDelete('cascade');
            $table->foreignId('committee_member_id')->constrained('users')->onDelete('cascade');
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['tender_id', 'committee_member_id'], 'tro_tender_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_review_otps');
    }
};
