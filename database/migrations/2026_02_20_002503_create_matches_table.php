<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bracket_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('round_number');
            $table->integer('match_number');

            $table->foreignId('player_one_id')
                ->nullable()
                ->constrained('players')
                ->nullOnDelete();

            $table->foreignId('player_two_id')
                ->nullable()
                ->constrained('players')
                ->nullOnDelete();

            $table->foreignId('winner_id')
                ->nullable()
                ->constrained('players')
                ->nullOnDelete();

            $table->enum('status', ['scheduled', 'completed'])
                ->default('scheduled');

            $table->timestamp('match_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
