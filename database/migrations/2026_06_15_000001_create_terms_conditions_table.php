<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('applicable_to', ['work_order', 'purchase_order', 'both'])->default('both');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default terms from existing presets
        $now = now();
        DB::table('terms_conditions')->insert([
            // Purchase Order terms
            ['title'=>'Price',                          'content'=>'Rates are firm and inclusive of packing, forwarding charges.',                                                                                                                                 'applicable_to'=>'purchase_order', 'sort_order'=>1,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Taxes',                          'content'=>'GST @ 18% is included in the quoted rates. No additional tax shall be payable.',                                                                                                              'applicable_to'=>'purchase_order', 'sort_order'=>2,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Delivery Mode',                  'content'=>'Immediate delivery upon receipt of the Purchase Order.',                                                                                                                                       'applicable_to'=>'purchase_order', 'sort_order'=>3,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Transportation',                 'content'=>'Included in VMRF-DU Scope.',                                                                                                                                                                  'applicable_to'=>'purchase_order', 'sort_order'=>4,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Payment Mode',                   'content'=>'100% advance payment against the Purchase Order.',                                                                                                                                             'applicable_to'=>'purchase_order', 'sort_order'=>5,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Quality',                        'content'=>'The Supplied product should be as per given specification. If any quality complaint arises, it should be replaced immediately by the supplier.',                                               'applicable_to'=>'purchase_order', 'sort_order'=>6,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Cancellation',                   'content'=>'The VMRF-DU reserves the right to cancel the purchase order in case of non-compliance with the agreed specifications, delivery schedule, or other terms and conditions.',                     'applicable_to'=>'purchase_order', 'sort_order'=>7,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Documents Required at Despatch', 'content'=>'Original Invoice with GST details, E-way Bill and warranty details.',                                                                                                                          'applicable_to'=>'purchase_order', 'sort_order'=>8,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            // Work Order terms
            ['title'=>'Duration',                       'content'=>'The work shall be completed within 30 days from the date of issuance of the work order as per discussion. If the work is not completed by the committed date, 0.5% of the total amount will be deducted.', 'applicable_to'=>'work_order', 'sort_order'=>1, 'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Pricing',                        'content'=>'Inclusive of any additional work that may be performed related with the work order.',                                                                                                          'applicable_to'=>'work_order',     'sort_order'=>2,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Payment Mode',                   'content'=>'50% advance payment against Work order. 40% Advance payment against payment request. Remaining 10% balance payment after work completion.',                                                    'applicable_to'=>'work_order',     'sort_order'=>3,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Quality & Service',              'content'=>'Should be superior. If any quality complaint arises, it should be rectified immediately.',                                                                                                     'applicable_to'=>'work_order',     'sort_order'=>4,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Safety',                         'content'=>'Workers must adhere to safety measures (helmet/safety jacket/safety rope) at the work place. The contractor is responsible for the workers safety at the workplace.',                          'applicable_to'=>'work_order',     'sort_order'=>5,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
            ['title'=>'Work Completion',                'content'=>'Work completion report should be approved by Property Management department.',                                                                                                                  'applicable_to'=>'work_order',     'sort_order'=>6,  'is_active'=>1, 'created_at'=>$now, 'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_conditions');
    }
};
