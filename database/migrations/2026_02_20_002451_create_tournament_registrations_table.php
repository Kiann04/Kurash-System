<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('player_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('age_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('weight_category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('weigh_in_weight', 8, 2)->nullable();

            $table->timestamps();

            // Prevent duplicate registration
            $table->unique(['tournament_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};