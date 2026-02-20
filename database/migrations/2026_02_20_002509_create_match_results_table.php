<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('player_one_score')->default(0);
            $table->integer('player_two_score')->default(0);

            $table->string('method')->nullable(); // submission, knockout, points

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};