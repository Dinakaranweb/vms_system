<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL 8.0+ or MariaDB 10.2+
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('draft', 'active', 'expired', 'terminated', 'completed') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('active', 'expired', 'terminated', 'completed') DEFAULT 'active'");
    }
};