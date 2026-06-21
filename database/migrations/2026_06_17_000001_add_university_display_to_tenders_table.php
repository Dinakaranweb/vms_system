<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'show_on_university')) {
                $table->boolean('show_on_university')->default(false);
            }
            if (!Schema::hasColumn('tenders', 'university_description')) {
                $table->text('university_description')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['show_on_university', 'university_description']);
        });
    }
};
