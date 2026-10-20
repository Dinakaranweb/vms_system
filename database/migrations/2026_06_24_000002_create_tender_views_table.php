<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('viewed_at')->useCurrent();

            $table->index(['tender_id', 'viewed_at']);
            $table->index(['tender_id', 'ip_address']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_views');
    }
};
