<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('location')->nullable()->after('name');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->date('membership_start_date')->nullable()->after('registered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn('membership_start_date');
        });
    }
};
