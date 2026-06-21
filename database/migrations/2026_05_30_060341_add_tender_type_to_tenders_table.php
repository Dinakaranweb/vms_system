<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            if (!Schema::hasColumn('tenders', 'tender_type')) {
                $table->enum('tender_type', ['online', 'postal'])->default('online')->after('status');
            }
            if (!Schema::hasColumn('tenders', 'postal_address')) {
                $table->text('postal_address')->nullable()->after('tender_type');
            }
            if (!Schema::hasColumn('tenders', 'submission_address')) {
                $table->text('submission_address')->nullable()->after('postal_address');
            }
            if (!Schema::hasColumn('tenders', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('submission_address');
            }
            if (!Schema::hasColumn('tenders', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_person');
            }
            if (!Schema::hasColumn('tenders', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $columns = ['tender_type', 'postal_address', 'submission_address', 
                       'contact_person', 'contact_phone', 'contact_email'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tenders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};