<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('tender_applications')->onDelete('cascade');
            $table->foreignId('committee_member_id')->constrained('users')->onDelete('cascade');
            $table->longText('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_review_comments');
    }
};
