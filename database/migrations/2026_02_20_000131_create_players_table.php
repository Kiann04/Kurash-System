<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();

            $table->string('full_name');
            $table->date('birthday');
            $table->string('club')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->unique();

            $table->string('emergency_contact');
            $table->string('emergency_contact_number');

            // Membership system
            $table->date('registered_at');
            $table->date('membership_expires_at');
            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};