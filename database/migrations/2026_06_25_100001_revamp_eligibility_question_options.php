<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            // New unified eligibility condition column for text/number field types
            $table->json('eligibility_condition')->nullable()->after('eligible_options');
        });
    }

    public function down(): void
    {
        Schema::table('eligibility_questions', function (Blueprint $table) {
            $table->dropColumn('eligibility_condition');
        });
    }
};
