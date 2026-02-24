<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class MatchSchedulerService
{
    /**
     * Re-calculate the global_match_order for a tournament.
     * This logic interleaves matches from different brackets within each round
     * to ensure athletes have maximum rest time.
     */
    public function scheduleTournament(Tournament $tournament)
    {
        // Load all matches with bracket info
        $bracketIds = $tournament->brackets()->pluck('id');
        $matches = TournamentMatch::whereIn('bracket_id', $bracketIds)
            ->orderBy('round_number')
            ->orderBy('bracket_id') // Initial sort
            ->orderBy('match_number')
            ->get();

        if ($matches->isEmpty()) {
            return;
        }

        // Group by Round Number
        $rounds = $matches->groupBy('round_number')->sortKeys();

        $orderedMatches = collect();

        foreach ($rounds as $roundNumber => $roundMatches) {
            // Within this round, group by Bracket ID
            $bracketGroups = $roundMatches->groupBy('bracket_id');
            
            // Interleave matches from these bracket groups
            $queues = $bracketGroups->values()->map(function ($group) {
                return $group->values()->all();
            })->all();
            
            $roundOrdered = collect();
            
            if (empty($queues)) {
                continue;
            }

            $maxQueueLength = max(array_map('count', $queues));

            for ($i = 0; $i < $maxQueueLength; $i++) {
                foreach ($queues as $queueIndex => $queue) {
                    if (isset($queue[$i])) {
                        $roundOrdered->push($queue[$i]);
                    }
                }
            }

            $orderedMatches = $orderedMatches->merge($roundOrdered);
        }

        // Assign global_match_order
        DB::transaction(function () use ($orderedMatches) {
            $order = 1;
            foreach ($orderedMatches as $match) {
                $match->global_match_order = $order++;
                $match->save();
            }
        });
    }

    /**
     * Refresh schedule for a specific tournament.
     */
    public function refreshSchedule($tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $this->scheduleTournament($tournament);
    }
}
