<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('weight_categories', function (Blueprint $table) {
            $table->string('gender', 6)->after('name'); // male or female
        });
    }

    public function down(): void
    {
        Schema::table('weight_categories', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};