<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('eligibility_questions', 'determines_eligibility')) {
                $table->boolean('determines_eligibility')->default(false)->after('is_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            if (Schema::hasColumn('eligibility_questions', 'determines_eligibility')) {
                $table->dropColumn('determines_eligibility');
            }
        });
    }
};