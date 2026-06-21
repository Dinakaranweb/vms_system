<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'order_type')) {
                $table->enum('order_type', ['purchase_order', 'work_order'])->nullable()->after('contract_number');
            }
            if (!Schema::hasColumn('contracts', 'order_number')) {
                $table->string('order_number')->nullable()->after('order_type');
            }
            if (!Schema::hasColumn('contracts', 'order_date')) {
                $table->date('order_date')->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('contracts', 'vmrfdu_ref')) {
                $table->string('vmrfdu_ref')->nullable()->after('order_date');
            }
            if (!Schema::hasColumn('contracts', 'supplier_ref')) {
                $table->string('supplier_ref')->nullable()->after('vmrfdu_ref');
            }
            if (!Schema::hasColumn('contracts', 'order_items')) {
                $table->json('order_items')->nullable()->after('supplier_ref');
            }
            if (!Schema::hasColumn('contracts', 'gst_rate')) {
                $table->decimal('gst_rate', 5, 2)->default(18)->after('order_items');
            }
            if (!Schema::hasColumn('contracts', 'order_discount')) {
                $table->decimal('order_discount', 15, 2)->default(0)->after('gst_rate');
            }
            if (!Schema::hasColumn('contracts', 'order_terms')) {
                $table->json('order_terms')->nullable()->after('order_discount');
            }
            if (!Schema::hasColumn('contracts', 'order_notes')) {
                $table->text('order_notes')->nullable()->after('order_terms');
            }
            if (!Schema::hasColumn('contracts', 'order_generated_at')) {
                $table->timestamp('order_generated_at')->nullable()->after('order_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $cols = ['order_type','order_number','order_date','vmrfdu_ref','supplier_ref',
                     'order_items','gst_rate','order_discount','order_terms','order_notes','order_generated_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('contracts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
