<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A registered vendor is no longer approved by the Purchase Officer alone.
 *
 * The officer's approval now only starts the process: the vendor then goes to the Purchase
 * Committee one member at a time, in the order the Super Admin has already defined on
 * purchase_committee_members.approval_order, and becomes a real vendor only when the last
 * member in that order has approved. One row here per member per vendor is that audit trail.
 *
 * This mirrors award_recommendation_approvals, which does the same job for award decisions,
 * so the two chains read and behave alike.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('committee_member_id')->constrained('users')->cascadeOnDelete();

            // Copied from purchase_committee_members at the moment the chain starts, so that
            // reordering the committee later cannot rewrite the history of a decision that
            // has already been taken.
            $table->unsignedInteger('approval_order');
            $table->string('designation')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('comments')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            // One step per member per vendor.
            $table->unique(['vendor_id', 'committee_member_id']);
            $table->index(['vendor_id', 'approval_order']);
            $table->index(['committee_member_id', 'status']);
        });

        // 'pending' now means "waiting on the Purchase Officer". Once the officer approves,
        // the vendor sits in committee_review until the final approver signs off.
        DB::statement(
            "ALTER TABLE vendors MODIFY COLUMN status
             ENUM('pending','committee_review','approved','rejected') NOT NULL DEFAULT 'pending'"
        );

        Schema::table('vendors', function (Blueprint $table) {
            // Who started the chain, as distinct from approved_by, which now records the
            // final committee approver rather than the officer.
            $table->foreignId('officer_approved_by')->nullable()->after('approved_at')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('officer_approved_at')->nullable()->after('officer_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('officer_approved_by');
            $table->dropColumn('officer_approved_at');
        });

        // Anything mid-chain has to land somewhere before the state disappears; the officer
        // has approved but the committee has not, so pending is the honest answer.
        DB::table('vendors')->where('status', 'committee_review')->update(['status' => 'pending']);

        DB::statement(
            "ALTER TABLE vendors MODIFY COLUMN status
             ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'"
        );

        Schema::dropIfExists('vendor_approvals');
    }
};
