<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->index('tournament_id', 'tournament_registrations_tournament_id_index');
            $table->index('player_id', 'tournament_registrations_player_id_index');
            $table->dropUnique('tournament_registrations_tournament_id_player_id_unique');
            $table->unique(
                ['tournament_id', 'player_id', 'weight_category_id'],
                'tournament_player_weight_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tournament_registrations', function (Blueprint $table) {
            $table->dropUnique('tournament_player_weight_unique');
            $table->dropIndex('tournament_registrations_tournament_id_index');
            $table->dropIndex('tournament_registrations_player_id_index');
            $table->unique(
                ['tournament_id', 'player_id'],
                'tournament_registrations_tournament_id_player_id_unique'
            );
        });
    }
};
