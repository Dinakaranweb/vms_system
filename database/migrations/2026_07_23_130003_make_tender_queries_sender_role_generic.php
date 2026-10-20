<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tender_queries MODIFY sender_role VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tender_queries MODIFY sender_role ENUM('super-admin','admin','tender-owner','management') NOT NULL");
    }
};
