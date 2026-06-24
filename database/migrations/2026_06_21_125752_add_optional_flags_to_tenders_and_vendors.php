<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->boolean('require_emd')->default(false)->after('emda_amount');
            $table->boolean('require_estimated_value')->default(false)->after('estimated_value');
            $table->boolean('email_notifications_enabled')->default(true)->after('notify_vendors_on_create');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('has_gst')->default(false)->after('gst_number');
        });

        Schema::table('tender_applications', function (Blueprint $table) {
            $table->text('technical_proposal_file')->nullable()->after('technical_proposal');
            $table->text('financial_proposal_file')->nullable()->after('financial_proposal');
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dropColumn(['require_emd', 'require_estimated_value', 'email_notifications_enabled']);
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('has_gst');
        });
        Schema::table('tender_applications', function (Blueprint $table) {
            $table->dropColumn(['technical_proposal_file', 'financial_proposal_file']);
        });
    }
};
