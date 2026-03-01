<?php

namespace App\Services;

use App\Models\Bracket;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class BronzeMatchService
{
    public function syncForBracket(Bracket $bracket): void
    {
        if ($bracket->format !== 'single_elimination' || $bracket->rounds < 2) {
            return;
        }

        $bronze = TournamentMatch::query()
            ->where('bracket_id', $bracket->id)
            ->where('is_bronze', true)
            ->first();

        if (!$bronze) {
            return;
        }

        if ($bronze->status === 'completed' && $bronze->winner_id) {
            return;
        }

        $semiRound = $bracket->rounds - 1;
        $semis = TournamentMatch::query()
            ->where('bracket_id', $bracket->id)
            ->where('round_number', $semiRound)
            ->where('is_bronze', false)
            ->orderBy('match_number')
            ->get();

        $losers = [];
        foreach ($semis as $semi) {
            if (!$semi->winner_id) {
                $losers[] = null;
                continue;
            }
            $losers[] = ((int) $semi->winner_id === (int) $semi->player_one_id)
                ? $semi->player_two_id
                : $semi->player_one_id;
        }

        $playerOne = $losers[0] ?? null;
        $playerTwo = $losers[1] ?? null;

        DB::transaction(function () use ($bronze, $playerOne, $playerTwo) {
            $updates = [
                'player_one_id' => $playerOne,
                'player_two_id' => $playerTwo,
            ];

            if ($playerOne === null && $playerTwo === null) {
                $updates['status'] = 'scheduled';
                $updates['winner_id'] = null;
            } elseif ($playerOne === null || $playerTwo === null) {
                $winnerId = $playerOne ?? $playerTwo;
                $updates['status'] = 'completed';
                $updates['winner_id'] = $winnerId;
            } else {
                if ($bronze->status !== 'completed') {
                    $updates['status'] = 'scheduled';
                    $updates['winner_id'] = null;
                }
            }

            $bronze->update($updates);
        });
    }
}
