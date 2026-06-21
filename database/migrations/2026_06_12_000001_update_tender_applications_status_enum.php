<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tender_applications MODIFY COLUMN status ENUM('submitted','under_review','shortlisted','rejected','awarded','withdrawn') NOT NULL DEFAULT 'submitted'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tender_applications MODIFY COLUMN status ENUM('submitted','shortlisted','rejected','awarded') NOT NULL DEFAULT 'submitted'");
    }
};
