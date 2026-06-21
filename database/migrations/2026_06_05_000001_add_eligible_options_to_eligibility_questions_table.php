<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            if (!Schema::hasColumn('eligibility_questions', 'eligible_options')) {
                $table->json('eligible_options')->nullable()->after('options');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            if (Schema::hasColumn('eligibility_questions', 'eligible_options')) {
                $table->dropColumn('eligible_options');
            }
        });
    }
};
