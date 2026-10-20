<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('published_date');
            $table->boolean('auto_publish')->default(false)->after('start_date');
            // opening_date was NOT NULL — make it nullable since it is now optional
            $table->date('opening_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'auto_publish']);
            $table->date('opening_date')->nullable(false)->change();
        });
    }
};
