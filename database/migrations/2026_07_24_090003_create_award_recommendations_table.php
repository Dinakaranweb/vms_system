<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained('tenders')->onDelete('cascade');
            $table->foreignId('application_id')->constrained('tender_applications')->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users');
            // pending_review: sequential committee approval in progress
            // committee_approved: everyone approved, OTP stage begins
            // otp_pending: OTPs issued, waiting on member verification
            // completed: all OTPs verified, ready for the Tender Owner to finalize the award/contract
            // rejected: a committee member rejected the recommendation
            $table->enum('status', ['pending_review', 'committee_approved', 'otp_pending', 'completed', 'rejected'])->default('pending_review');
            $table->text('initiation_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['tender_id', 'application_id']);
        });

        Schema::create('award_recommendation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_recommendation_id')->constrained('award_recommendations')->onDelete('cascade');
            $table->foreignId('committee_member_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('approval_order');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->longText('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            // OTP fields for the final sign-off stage
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['award_recommendation_id', 'committee_member_id'], 'ara_recommendation_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_recommendation_approvals');
        Schema::dropIfExists('award_recommendations');
    }
};
