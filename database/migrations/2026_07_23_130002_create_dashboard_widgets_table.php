<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->enum('widget_type', ['stat_grid', 'list'])->default('stat_grid');
            $table->string('data_source'); // maps to a method on App\Services\WidgetDataProvider
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('role_dashboard_widget', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->foreignId('widget_id')->constrained('dashboard_widgets')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['role_id', 'widget_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_dashboard_widget');
        Schema::dropIfExists('dashboard_widgets');
    }
};
