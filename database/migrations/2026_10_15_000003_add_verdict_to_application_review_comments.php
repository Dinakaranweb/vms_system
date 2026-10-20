<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional verdict alongside a committee member's written recommendation.
 *
 * The committee's recommendations are advisory: a member may support or oppose awarding to a
 * vendor, or simply leave a remark with no position at all. The Purchase Officer sees the
 * tally when finalizing but is not bound by it, which is why this is nullable rather than
 * defaulted — "no position recorded" is a real and common state, distinct from "neutral".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_review_comments', function (Blueprint $table) {
            if (!Schema::hasColumn('application_review_comments', 'verdict')) {
                $table->enum('verdict', ['support', 'oppose', 'neutral'])->nullable()->after('comment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_review_comments', function (Blueprint $table) {
            if (Schema::hasColumn('application_review_comments', 'verdict')) {
                $table->dropColumn('verdict');
            }
        });
    }
};
