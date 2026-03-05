<script setup lang="ts">
/**
 * BracketView Component
 * 
 * Displays tournament brackets with interactive match management.
 * Features:
 * - List of all brackets (categories)
 * - Detailed single elimination bracket view
 * - Match winner selection
 * - Award podium display (Gold, Silver, Bronze)
 * - Fullscreen mode toggle
 */
import { Trophy, ArrowLeft, Maximize2, Minimize2, AlertCircle, Medal, Crown } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { MatchItem, BracketItem } from '@/types/bracket'
import { useBracketLogic } from '@/composables/useBracketLogic'

/**
 * Component props
 * @property brackets - List of all bracket items for the tournament
 * @property activeBracketId - ID of the currently selected bracket to view details
 * @property fullscreenBracketId - ID of the bracket currently in fullscreen mode
 * @property isCompleted - Whether the tournament is marked as completed (read-only mode)
 */
const props = defineProps<{
    brackets: BracketItem[]
    activeBracketId: number | null
    fullscreenBracketId: number | null
    isCompleted: boolean
}>()

/**
 * Component events
 */
const emit = defineEmits<{
    (e: 'selectBracket', id: number): void
    (e: 'clearSelection'): void
    (e: 'toggleFullScreen', id: number): void
    (e: 'chooseWinner', match: MatchItem, winnerId: number | null): void
}>()

/**
 * Bracket logic composable
 * Provides helper functions for rendering the bracket structure and determining match status.
 */
const { 
    formatLabel, 
    safeAwards, 
    roundsForBracket, 
    roundLabel, 
    finalRoundNumber, 
    roundColumnStyle, 
    bronzeMatchFor, 
    matchReady 
} = useBracketLogic()

/**
 * Selects a bracket to view its details.
 * @param id Bracket ID
 */
const selectBracket = (id: number) => emit('selectBracket', id)

/**
 * Clears the currently selected bracket, returning to the list view.
 */
const clearSelection = () => emit('clearSelection')

/**
 * Toggles fullscreen mode for a specific bracket.
 * @param id Bracket ID
 */
const toggleFullScreen = (id: number) => emit('toggleFullScreen', id)

/**
 * Emits an event to choose a winner for a match.
 * @param match The match object
 * @param winnerId The ID of the winning player
 */
const chooseWinner = (match: MatchItem, winnerId: number | null) => emit('chooseWinner', match, winnerId)

</script>

<template>
    <div>
        <Alert v-if="brackets.length === 0" variant="default">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>No Brackets</AlertTitle>
            <AlertDescription>
                No brackets have been generated for this tournament yet.
            </AlertDescription>
        </Alert>

        <!-- Brackets Summary Table -->
        <Card v-if="brackets.length > 0 && !activeBracketId" class="overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
            <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4 flex flex-row items-center justify-between space-y-0">
                <div class="space-y-1">
                    <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Tournament Brackets
                    </CardTitle>
                    <CardDescription>Select a category to view its bracket.</CardDescription>
                </div>
                <Badge variant="outline" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 shadow-sm">{{ brackets.length }} Categories</Badge>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm">
                        <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                            <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Category</TableHead>
                            <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Format</TableHead>
                            <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Entrants</TableHead>
                            <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Champion</TableHead>
                            <TableHead class="text-right font-semibold text-slate-500 dark:text-slate-400">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow 
                            v-for="bracket in brackets" 
                            :key="bracket.id" 
                            @click="selectBracket(bracket.id)"
                            class="cursor-pointer hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800"
                        >
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ bracket.gender }} - {{ bracket.age_category }}</span>
                                    <span class="text-xs text-muted-foreground">{{ bracket.weight_category }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge :variant="bracket.format === 'single_elimination' ? 'secondary' : 'outline'">
                                    {{ formatLabel(bracket.format) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center font-medium">
                                {{ bracket.entrant_count ?? 0 }}
                            </TableCell>
                            <TableCell>
                                <div v-if="bracket.champion" class="flex items-center gap-2 text-amber-600">
                                    <Trophy class="h-4 w-4 text-amber-500" />
                                    <span class="font-medium text-sm">{{ bracket.champion }}</span>
                                </div>
                                <span v-else class="text-muted-foreground text-xs italic">TBD</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="sm">
                                    View
                                    <ArrowLeft class="ml-2 h-4 w-4 rotate-180" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Active Bracket View -->
        <template v-if="activeBracketId">
            <div 
                v-for="bracket in brackets.filter(b => b.id === activeBracketId)" 
                :key="bracket.id" 
                :id="`bracket-section-${bracket.id}`"
                class="space-y-4 bracket-section"
            >
                <Card class="shadow-sm dark:bg-slate-950 dark:border-slate-800">
                    <CardContent class="p-4">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" @click="clearSelection" class="dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    <ArrowLeft class="h-4 w-4 mr-2" />
                                    Back to List
                                </Button>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <Badge variant="default" class="bg-indigo-600 hover:bg-indigo-700">{{ (bracket.gender || 'unknown').toUpperCase() }}</Badge>
                                    <Badge variant="secondary" class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ bracket.age_category || '-' }}</Badge>
                                    <Badge variant="outline" class="dark:border-slate-700 dark:text-slate-300">{{ bracket.weight_category || '-' }}</Badge>
                                    <Badge variant="outline" class="dark:border-slate-700 dark:text-slate-300">{{ formatLabel(bracket.format) }}</Badge>
                                    <span class="text-sm text-muted-foreground ml-2">{{ bracket.entrant_count ?? 0 }} Entrants</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex gap-4 mr-4 text-xs font-medium text-slate-600 dark:text-slate-400">
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 block shadow-sm"></span> Blue (Upper)</span>
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 block shadow-sm"></span> Green (Lower)</span>
                                </div>
                                <Button variant="ghost" size="icon" @click="toggleFullScreen(bracket.id)" title="Full Screen" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
                                    <component :is="fullscreenBracketId === bracket.id ? Minimize2 : Maximize2" class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-3">
                            <Card class="bg-amber-50/50 border-amber-200 dark:bg-amber-950/20 dark:border-amber-900/50">
                                <CardContent class="p-3 flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-500">
                                        <Medal class="h-5 w-5" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs uppercase font-bold text-amber-600 dark:text-amber-500 tracking-wider">Gold</span>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ safeAwards(bracket).gold || '-' }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="bg-slate-50/50 border-slate-200 dark:bg-slate-900/50 dark:border-slate-800">
                                <CardContent class="p-3 flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-400">
                                        <Medal class="h-5 w-5" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs uppercase font-bold text-slate-600 dark:text-slate-400 tracking-wider">Silver</span>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ safeAwards(bracket).silver || '-' }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card class="bg-orange-50/50 border-orange-200 dark:bg-orange-950/20 dark:border-orange-900/50">
                                <CardContent class="p-3 flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-500">
                                        <Medal class="h-5 w-5" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs uppercase font-bold text-orange-600 dark:text-orange-500 tracking-wider">Bronze</span>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100" v-if="safeAwards(bracket).bronze.length">{{ safeAwards(bracket).bronze.join(', ') }}</span>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100" v-else>-</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </CardContent>
                </Card>

                <!-- Bracket Board Renderers -->
                <div class="overflow-auto pb-6">
                    <template v-if="bracket.format === 'single_elimination'">
                        <div class="bracket-container standard-bracket flex items-start gap-12 overflow-auto py-8 px-4">
                            <div
                                v-for="round in roundsForBracket(bracket)"
                                :key="round.round"
                                class="se-round flex flex-col"
                            >
                                <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, bracket.entrant_count, 'single_elimination') }}</h3>
                                <div class="se-round-stack flex flex-col" :style="roundColumnStyle(round.round)">
                                    <article 
                                        v-for="match in round.matches" 
                                        :key="match.id" 
                                        class="se-match-container"
                                        :data-match-number="match.match_number"
                                    >
                                        <!-- Match Number on the left for Round 1 -->
                                        <div v-if="round.round === 1" class="match-number-left">
                                            {{ match.match_number }}
                                        </div>

                                        <div class="se-match">
                                            <div class="match-card-body flex flex-col border border-slate-200 dark:border-slate-700 rounded-md overflow-hidden shadow-sm relative">
                                                <!-- Fighter Blue (Upper) -->
                                                <button
                                                     class="fighter fighter-blue"
                                                     :class="{ 
                                                        'winner': match.winner_id === match.player_one_id && match.player_one_id !== null,
                                                        'opacity-50': match.status === 'completed' && match.winner_id !== match.player_one_id
                                                     }"
                                                     :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                                     @click="chooseWinner(match, match.player_one_id)"
                                                 >
                                                    <div class="fighter-inner">
                                                        <div class="fighter-color-indicator"></div>
                                                        <div class="fighter-name">
                                                            <span v-if="match.player_one_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ match.player_one_seed }}</span>
                                                            <span class="truncate">{{ match.player_one || (match.round_number === 1 ? 'BYE' : 'TBD') }}</span>
                                                            <Crown v-if="match.winner_id === match.player_one_id && match.player_one_id !== null" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                        </div>
                                                    </div>
                                                 </button>

                                                 <!-- Fighter Green (Lower) -->
                                                 <button
                                                     class="fighter fighter-green border-t border-slate-100 dark:border-slate-800"
                                                     :class="{ 
                                                        'winner': match.winner_id === match.player_two_id && match.player_two_id !== null,
                                                        'opacity-50': match.status === 'completed' && match.winner_id !== match.player_two_id
                                                     }"
                                                     :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                                     @click="chooseWinner(match, match.player_two_id)"
                                                 >
                                                    <div class="fighter-inner">
                                                        <div class="fighter-color-indicator"></div>
                                                        <div class="fighter-name">
                                                            <span v-if="match.player_two_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ match.player_two_seed }}</span>
                                                            <span class="truncate">{{ match.player_two || (match.round_number === 1 ? 'BYE' : 'TBD') }}</span>
                                                            <Crown v-if="match.winner_id === match.player_two_id && match.player_two_id !== null" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>

                            <div v-if="bronzeMatchFor(bracket)" class="bronze-board ml-8 self-end mb-8">
                                <h3 class="round-title">Bronze Match</h3>
                                <div class="se-round-stack">
                                    <article class="se-match-container">
                                        <div class="se-match bronze-match">
                                            <div class="match-card-body flex flex-col border border-slate-200 dark:border-slate-700 rounded-md overflow-hidden shadow-sm relative">
                                                <!-- Fighter Blue (Upper) -->
                                                <button
                                                     class="fighter fighter-blue"
                                                     :class="{ 
                                                        'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_one_id && bronzeMatchFor(bracket)?.player_one_id !== null,
                                                        'opacity-50': bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_one_id
                                                     }"
                                                     :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_one_id)"
                                                     @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_one_id ?? null)"
                                                 >
                                                    <div class="fighter-inner">
                                                        <div class="fighter-color-indicator"></div>
                                                        <div class="fighter-name">
                                                            <span v-if="bronzeMatchFor(bracket)?.player_one_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ bronzeMatchFor(bracket)?.player_one_seed }}</span>
                                                            <span class="truncate">{{ bronzeMatchFor(bracket)?.player_one || 'TBD' }}</span>
                                                            <Crown v-if="bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_one_id && bronzeMatchFor(bracket)?.player_one_id !== null" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                        </div>
                                                    </div>
                                                 </button>

                                                 <!-- Fighter Green (Lower) -->
                                                 <button
                                                     class="fighter fighter-green border-t border-slate-100 dark:border-slate-800"
                                                     :class="{ 
                                                        'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_two_id && bronzeMatchFor(bracket)?.player_two_id !== null,
                                                        'opacity-50': bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_two_id
                                                     }"
                                                     :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_two_id)"
                                                     @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_two_id ?? null)"
                                                 >
                                                    <div class="fighter-inner">
                                                        <div class="fighter-color-indicator"></div>
                                                        <div class="fighter-name">
                                                            <span v-if="bronzeMatchFor(bracket)?.player_two_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ bronzeMatchFor(bracket)?.player_two_seed }}</span>
                                                            <span class="truncate">{{ bronzeMatchFor(bracket)?.player_two || 'TBD' }}</span>
                                                            <Crown v-if="bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_two_id && bronzeMatchFor(bracket)?.player_two_id !== null" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div class="rr-board">
                            <div v-for="round in roundsForBracket(bracket)" :key="round.round" class="rr-round">
                                <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, bracket.entrant_count, 'round_robin') }}</h3>
                                <div class="rr-grid">
                                    <article v-for="match in round.matches" :key="match.id" class="rr-match-container">
                                        <div class="flex items-center justify-between px-1 mb-1">
                                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider dark:text-slate-400">Match {{ match.match_number }}</span>
                                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider dark:text-slate-500">{{ match.status }}</span>
                                        </div>

                                        <div class="match-card-body flex flex-col border border-slate-200 dark:border-slate-700 rounded-md overflow-hidden shadow-sm relative">
                                             <button
                                                  class="fighter fighter-blue"
                                                  :class="{ 'winner': match.winner_id === match.player_one_id }"
                                                  :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                                  @click="chooseWinner(match, match.player_one_id)"
                                              >
                                                 <div class="fighter-inner">
                                                     <div class="fighter-color-indicator"></div>
                                                     <div class="fighter-name">
                                                         <span v-if="match.player_one_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ match.player_one_seed }}</span>
                                                         <span class="truncate">{{ match.player_one || 'BYE' }}</span>
                                                         <Crown v-if="match.winner_id === match.player_one_id" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                     </div>
                                                 </div>
                                              </button>
                                              <button
                                                  class="fighter fighter-green border-t border-slate-100 dark:border-slate-800"
                                                  :class="{ 'winner': match.winner_id === match.player_two_id }"
                                                  :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                                  @click="chooseWinner(match, match.player_two_id)"
                                              >
                                                 <div class="fighter-inner">
                                                     <div class="fighter-color-indicator"></div>
                                                     <div class="fighter-name">
                                                         <span v-if="match.player_two_seed" class="text-xs text-slate-400 mr-1.5 font-bold">#{{ match.player_two_seed }}</span>
                                                         <span class="truncate">{{ match.player_two || 'BYE' }}</span>
                                                         <Crown v-if="match.winner_id === match.player_two_id" class="h-3 w-3 text-yellow-500 fill-yellow-500 ml-1" />
                                                     </div>
                                                 </div>
                                              </button>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Round title styling */
.round-title {
    font-size: 0.875rem;
    line-height: 1.25rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 1.5rem;
    text-align: center;
}
.dark .round-title {
    color: #94a3b8;
}

/* Match card styling */
.se-match-container {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.match-number-left {
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 900;
    color: #94a3b8;
    width: 1.25rem;
    text-align: right;
}

.se-match {
    display: flex;
    flex-direction: column;
    width: 100%;
    position: relative;
    z-index: 10;
    min-width: 220px;
}

.match-card-body {
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    overflow: hidden;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
    position: relative;
    height: 74px;
}

.dark .match-card-body {
    background-color: #0f172a;
    border-color: #1e293b;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
}

/* Fighter button styling */
.fighter {
    display: flex;
    flex-direction: column;
    width: 100%;
    padding: 0;
    text-align: left;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
    position: relative;
}

.fighter-inner {
    display: flex;
    align-items: stretch;
    height: 2.25rem;
}

.fighter-color-indicator {
    width: 0.375rem;
    flex-shrink: 0;
}

.fighter-blue .fighter-color-indicator {
    background-color: #2563eb;
}

.fighter-green .fighter-color-indicator {
    background-color: #059669;
}

.fighter-name {
    display: flex;
    align-items: center;
    flex: 1 1 0%;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    color: #334155;
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 600;
}

.fighter-blue .fighter-name {
    background-color: rgb(239 246 255 / 0.5);
}

.fighter-green .fighter-name {
    background-color: rgb(236 253 245 / 0.5);
}

.dark .fighter-name {
    color: #cbd5e1;
}

.dark .fighter-blue .fighter-name {
    background-color: rgb(30 58 138 / 0.1);
}

.dark .fighter-green .fighter-name {
    background-color: rgb(6 78 59 / 0.1);
}

/* Winner state */
.fighter.winner .fighter-name {
    font-weight: 700;
}

.fighter-blue.winner .fighter-name {
    background-color: #2563eb;
    color: white;
}
.dark .fighter-blue.winner .fighter-name {
    background-color: #2563eb;
    color: white;
}

.fighter-green.winner .fighter-name {
    background-color: #059669;
    color: white;
}
.dark .fighter-green.winner .fighter-name {
    background-color: #059669;
    color: white;
}

.fighter-blue:not(.winner):not(:disabled):hover .fighter-name {
    background-color: rgb(219 234 254 / 0.5);
}
.dark .fighter-blue:not(.winner):not(:disabled):hover .fighter-name {
    background-color: rgb(30 58 138 / 0.3);
}

.fighter-green:not(.winner):not(:disabled):hover .fighter-name {
    background-color: rgb(209 250 229 / 0.5);
}
.dark .fighter-green:not(.winner):not(:disabled):hover .fighter-name {
    background-color: rgb(6 78 59 / 0.3);
}

/* Bracket connection lines (Standard Fork Shape) */
.standard-bracket .se-round:not(:last-child) .match-card-body::after {
    content: "";
    position: absolute;
    right: -1.5rem;
    top: 50%;
    width: 1.5rem;
    border-top-width: 2px;
    border-style: solid;
    border-color: #cbd5e1;
}
.dark .standard-bracket .se-round:not(:last-child) .match-card-body::after {
    border-color: #334155;
}

/* Connector Left for rounds after the first */
.standard-bracket .se-round:not(:first-child) .match-card-body::before {
    content: "";
    position: absolute;
    left: -1.5rem;
    top: 50%;
    width: 1.5rem;
    border-top-width: 2px;
    border-style: solid;
    border-color: #cbd5e1;
}
.dark .standard-bracket .se-round:not(:first-child) .match-card-body::before {
    border-color: #334155;
}

/* Vertical line Down for Odd matches */
.standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(odd)::after {
    content: "";
    position: absolute;
    right: -1.5rem;
    width: 0;
    border-right-width: 2px;
    border-style: solid;
    border-color: #cbd5e1;
    top: 50%;
    height: calc(50% + (var(--row-gap) / 2));
    z-index: 0;
}
.dark .standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(odd)::after {
    border-color: #334155;
}

/* Vertical line Up for Even matches */
.standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(even)::after {
    content: "";
    position: absolute;
    right: -1.5rem;
    width: 0;
    border-right-width: 2px;
    border-style: solid;
    border-color: #cbd5e1;
    bottom: 50%;
    height: calc(50% + (var(--row-gap) / 2));
    z-index: 0;
}
.dark .standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(even)::after {
    border-color: #334155;
}

/* Round title styling in full screen */
.bracket-section:fullscreen .round-title {
    margin-bottom: 2.5rem;
    font-size: 1rem;
    line-height: 1.5rem;
}

.bracket-section:fullscreen .se-match, 
.bracket-section:fullscreen .rr-match-container {
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    min-width: 280px;
}

/* Round Robin styling */
.rr-board {
    display: grid;
    gap: 2rem;
}

.rr-round {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background-color: white;
    padding: 1.5rem;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}
.dark .rr-round {
    background-color: #0f172a;
    border-color: #1e293b;
}

.rr-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.bracket-section:fullscreen .rr-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}
</style>
