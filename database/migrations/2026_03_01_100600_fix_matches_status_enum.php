<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Normalize any unexpected values before constraining the enum.
            DB::statement("UPDATE `matches` SET `status` = 'scheduled' WHERE `status` IS NULL OR `status` NOT IN ('scheduled', 'completed')");
            DB::statement("ALTER TABLE `matches` MODIFY `status` ENUM('scheduled','completed') NOT NULL DEFAULT 'scheduled'");
            return;
        }

        // No-op for non-MySQL drivers.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `matches` MODIFY `status` ENUM('completed') NOT NULL DEFAULT 'completed'");
            return;
        }
        // No-op for non-MySQL drivers.
    }
};
