<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('icon')->nullable();
            $table->string('route_name')->nullable();
            $table->string('section_label')->nullable();
            // 'section' items represent a whole hardcoded nav block (coarse control for the 6 built-in roles);
            // 'item' items are individually-routable links (used by the fully dynamic custom-role Portal menu).
            $table->enum('scope_type', ['section', 'item'])->default('item');
            $table->string('permission_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('role_menu_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['role_id', 'menu_item_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menu_item');
        Schema::dropIfExists('menu_items');
    }
};
