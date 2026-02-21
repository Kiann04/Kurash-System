<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'

interface MatchItem {
    id: number
    round_number: number
    match_number: number
    status: 'scheduled' | 'completed'
    player_one_id: number | null
    player_one: string | null
    player_two_id: number | null
    player_two: string | null
    winner_id: number | null
    winner: string | null
}

interface BracketItem {
    id: number
    gender: string | null
    age_category: string | null
    weight_category: string | null
    format: 'round_robin' | 'single_elimination' | null
    rounds: number
    entrant_count?: number
    champion: string | null
    awards?: {
        gold: string | null
        silver: string | null
        bronze: string[]
    }
    matches?: MatchItem[]
}

interface TournamentItem {
    id: number
    name: string
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count?: number
}

interface CategoryParticipant {
    gender: string
    age_category: string
    weight_category: string
    participant_count: number
}

const props = defineProps<{
    tournament: TournamentItem
    category_participants?: CategoryParticipant[]
    brackets: BracketItem[]
}>()

const isCompleted = props.tournament.status === 'completed'

const safeAwards = (bracket: BracketItem) => {
    return bracket.awards ?? { gold: null, silver: null, bronze: [] }
}

const safeMatches = (bracket: BracketItem): MatchItem[] => {
    return bracket.matches ?? []
}

const roundsFor = (matches: MatchItem[]) => {
    const buckets: Record<number, MatchItem[]> = {}

    matches.forEach((match) => {
        if (!buckets[match.round_number]) buckets[match.round_number] = []
        buckets[match.round_number].push(match)
    })

    return Object.entries(buckets)
        .map(([round, items]) => ({
            round: Number(round),
            matches: [...items].sort((a, b) => a.match_number - b.match_number),
        }))
        .sort((a, b) => a.round - b.round)
}

const roundsForBracket = (bracket: BracketItem) => roundsFor(safeMatches(bracket))

const finalRoundNumber = (bracket: BracketItem) => {
    const rounds = roundsForBracket(bracket)
    return rounds.length ? Math.max(...rounds.map((r) => r.round)) : 1
}

const eastRounds = (bracket: BracketItem) => {
    const finalRound = finalRoundNumber(bracket)

    return roundsForBracket(bracket)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.filter((match) => match.match_number <= round.matches.length / 2),
        }))
}

const westRounds = (bracket: BracketItem) => {
    const finalRound = finalRoundNumber(bracket)

    return roundsForBracket(bracket)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.filter((match) => match.match_number > round.matches.length / 2),
        }))
}

const grandFinalMatches = (bracket: BracketItem) => {
    const finalRound = finalRoundNumber(bracket)
    const final = roundsForBracket(bracket).find((round) => round.round === finalRound)
    return final?.matches ?? []
}

const roundLabel = (totalRounds: number, roundNumber: number, conference?: 'East' | 'West') => {
    if (roundNumber === totalRounds) return 'Grand Final'

    const distance = totalRounds - roundNumber
    if (distance === 1) return conference ? `${conference} Final` : 'Final'
    if (distance === 2) return 'Semifinal'
    if (distance === 3) return 'Quarterfinal'

    return `Round ${roundNumber}`
}

const formatLabel = (format: BracketItem['format']) => {
    if (format === 'single_elimination') return 'Single Elimination'
    if (format === 'round_robin') return 'Round Robin'
    return 'Unknown'
}

const roundColumnStyle = (roundNumber: number) => {
    const multiplier = Math.max(1, Math.pow(2, roundNumber - 1))
    return {
        marginTop: `${(multiplier - 1) * 18}px`,
        rowGap: `${multiplier * 14}px`,
    }
}

const chooseWinner = (match: MatchItem, winnerId: number | null) => {
    if (isCompleted || !winnerId) return

    router.post(
        route('admin.tournaments.matches.advance', {
            tournament: props.tournament.id,
            match: match.id,
        }),
        { winner_id: winnerId },
        { preserveScroll: true }
    )
}

const exportPdf = () => {
    if (!isCompleted) return
    window.print()
}
</script>

<template>
    <Head :title="`Brackets - ${props.tournament.name}`" />

    <AppLayout>
        <div class="p-6 space-y-6 bracket-page">
            <div class="bracket-header print:hidden">
                <div>
                    <h1 class="text-2xl font-bold">{{ props.tournament.name }} Bracketing</h1>
                    <p class="text-sm text-slate-500">
                        {{ props.tournament.tournament_date }} | {{ props.tournament.status }}
                    </p>
                    <p class="text-sm text-slate-500">
                        Total Registered: {{ props.tournament.registrations_count ?? 0 }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="isCompleted"
                        class="px-3 py-2 rounded bg-emerald-600 text-white"
                        @click="exportPdf"
                    >
                        Export PDF
                    </button>
                    <Link :href="route('admin.brackets.index')" class="px-3 py-2 rounded border border-slate-300">
                        Back
                    </Link>
                </div>
            </div>

            <div v-if="props.brackets.length === 0" class="border rounded-lg p-4 text-sm text-slate-500 bg-white">
                No brackets generated yet for this tournament.
            </div>

            <div v-if="(props.category_participants ?? []).length" class="rounded-xl border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold mb-3">Category Participants</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-slate-500">
                            <tr>
                                <th class="text-left p-2">Gender</th>
                                <th class="text-left p-2">Age Category</th>
                                <th class="text-left p-2">Weight Category</th>
                                <th class="text-right p-2">Participants</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in (props.category_participants ?? [])" :key="`${item.gender}-${item.age_category}-${item.weight_category}-${idx}`" class="border-t">
                                <td class="p-2">{{ item.gender }}</td>
                                <td class="p-2">{{ item.age_category }}</td>
                                <td class="p-2">{{ item.weight_category }}</td>
                                <td class="p-2 text-right font-semibold">{{ item.participant_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <section v-for="bracket in props.brackets" :key="bracket.id" class="space-y-4">
                <div class="rounded-xl border border-slate-200 p-4 bg-white">
                    <div class="flex flex-wrap gap-2 text-sm items-center">
                        <span class="tag">{{ (bracket.gender || 'unknown').toUpperCase() }}</span>
                        <span class="tag">{{ bracket.age_category || '-' }}</span>
                        <span class="tag">{{ bracket.weight_category || '-' }}</span>
                        <span class="tag">{{ formatLabel(bracket.format) }}</span>
                        <span class="tag">{{ bracket.entrant_count ?? 0 }} Entrants</span>
                        <span class="tag blue">Blue = upper fighter</span>
                        <span class="tag green">Green = lower fighter</span>
                        <span v-if="bracket.champion" class="tag champion">Champion: {{ bracket.champion }}</span>
                    </div>
                    <div class="mt-3 grid gap-2 md:grid-cols-3 text-sm">
                        <div class="rounded-lg border border-amber-300 bg-amber-50 p-2">
                            <strong>Gold:</strong> {{ safeAwards(bracket).gold || '-' }}
                        </div>
                        <div class="rounded-lg border border-slate-300 bg-slate-50 p-2">
                            <strong>Silver:</strong> {{ safeAwards(bracket).silver || '-' }}
                        </div>
                        <div class="rounded-lg border border-orange-300 bg-orange-50 p-2">
                            <strong>Bronze:</strong>
                            <span v-if="safeAwards(bracket).bronze.length">{{ safeAwards(bracket).bronze.join(', ') }}</span>
                            <span v-else>-</span>
                        </div>
                    </div>
                </div>

                <div v-if="bracket.format === 'single_elimination'" class="se-board">
                    <div class="conference-board">
                        <div class="conference-side east">
                            <div
                                v-for="round in eastRounds(bracket)"
                                :key="`east-${round.round}`"
                                class="se-round"
                                :style="roundColumnStyle(round.round)"
                            >
                                <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, 'East') }}</h3>
                                <div class="se-round-stack">
                                    <article v-for="match in round.matches" :key="`east-${match.id}`" class="se-match">
                                        <div class="match-head">
                                            <span>Match {{ match.match_number }}</span>
                                            <span>{{ match.status }}</span>
                                        </div>

                                        <button
                                            class="fighter fighter-blue"
                                            :class="match.winner_id === match.player_one_id ? 'winner' : ''"
                                            :disabled="isCompleted || !match.player_one_id"
                                            @click="chooseWinner(match, match.player_one_id)"
                                        >
                                            {{ match.player_one || 'BYE' }}
                                        </button>

                                        <button
                                            class="fighter fighter-green"
                                            :class="match.winner_id === match.player_two_id ? 'winner' : ''"
                                            :disabled="isCompleted || !match.player_two_id"
                                            @click="chooseWinner(match, match.player_two_id)"
                                        >
                                            {{ match.player_two || 'BYE' }}
                                        </button>
                                    </article>
                                </div>
                            </div>
                        </div>

                        <div class="conference-center">
                            <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), finalRoundNumber(bracket)) }}</h3>
                            <div class="se-round-stack final-stack">
                                <article
                                    v-for="match in grandFinalMatches(bracket)"
                                    :key="`final-${match.id}`"
                                    class="se-match grand-final"
                                >
                                    <div class="match-head">
                                        <span>Grand Final</span>
                                        <span>{{ match.status }}</span>
                                    </div>
                                    <button
                                        class="fighter fighter-blue"
                                        :class="match.winner_id === match.player_one_id ? 'winner' : ''"
                                        :disabled="isCompleted || !match.player_one_id"
                                        @click="chooseWinner(match, match.player_one_id)"
                                    >
                                        {{ match.player_one || 'BYE' }}
                                    </button>
                                    <button
                                        class="fighter fighter-green"
                                        :class="match.winner_id === match.player_two_id ? 'winner' : ''"
                                        :disabled="isCompleted || !match.player_two_id"
                                        @click="chooseWinner(match, match.player_two_id)"
                                    >
                                        {{ match.player_two || 'BYE' }}
                                    </button>
                                </article>
                            </div>
                        </div>

                        <div class="conference-side west">
                            <div
                                v-for="round in westRounds(bracket)"
                                :key="`west-${round.round}`"
                                class="se-round"
                                :style="roundColumnStyle(round.round)"
                            >
                                <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, 'West') }}</h3>
                                <div class="se-round-stack">
                                    <article v-for="match in round.matches" :key="`west-${match.id}`" class="se-match">
                                        <div class="match-head">
                                            <span>Match {{ match.match_number }}</span>
                                            <span>{{ match.status }}</span>
                                        </div>

                                        <button
                                            class="fighter fighter-blue"
                                            :class="match.winner_id === match.player_one_id ? 'winner' : ''"
                                            :disabled="isCompleted || !match.player_one_id"
                                            @click="chooseWinner(match, match.player_one_id)"
                                        >
                                            {{ match.player_one || 'BYE' }}
                                        </button>

                                        <button
                                            class="fighter fighter-green"
                                            :class="match.winner_id === match.player_two_id ? 'winner' : ''"
                                            :disabled="isCompleted || !match.player_two_id"
                                            @click="chooseWinner(match, match.player_two_id)"
                                        >
                                            {{ match.player_two || 'BYE' }}
                                        </button>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="rr-board">
                    <div v-for="round in roundsForBracket(bracket)" :key="round.round" class="rr-round">
                        <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round) }}</h3>
                        <div class="rr-grid">
                            <article v-for="match in round.matches" :key="match.id" class="rr-match">
                                <div class="match-head">
                                    <span>Match {{ match.match_number }}</span>
                                    <span>{{ match.status }}</span>
                                </div>
                                <button
                                    class="fighter fighter-blue"
                                    :class="match.winner_id === match.player_one_id ? 'winner' : ''"
                                    :disabled="isCompleted || !match.player_one_id"
                                    @click="chooseWinner(match, match.player_one_id)"
                                >
                                    {{ match.player_one || 'BYE' }}
                                </button>
                                <button
                                    class="fighter fighter-green"
                                    :class="match.winner_id === match.player_two_id ? 'winner' : ''"
                                    :disabled="isCompleted || !match.player_two_id"
                                    @click="chooseWinner(match, match.player_two_id)"
                                >
                                    {{ match.player_two || 'BYE' }}
                                </button>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.bracket-page {
    background:
        radial-gradient(circle at 10% 8%, #e8f1ff 0, transparent 35%),
        radial-gradient(circle at 88% 10%, #eafaf0 0, transparent 33%),
        #f8fafc;
}

.bracket-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tag {
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    padding: 4px 10px;
    background: #f8fafc;
}

.tag.blue {
    background: #dbeafe;
    border-color: #93c5fd;
}

.tag.green {
    background: #dcfce7;
    border-color: #86efac;
}

.tag.champion {
    background: #fef3c7;
    border-color: #fcd34d;
    font-weight: 700;
}

.se-board {
    overflow-x: auto;
    padding-bottom: 6px;
}

.conference-board {
    min-width: 1250px;
    display: grid;
    grid-template-columns: 1fr 360px 1fr;
    gap: 22px;
    align-items: start;
}

.conference-side {
    display: grid;
    grid-auto-flow: column;
    grid-auto-columns: 270px;
    gap: 18px;
}

.conference-side.west {
    direction: rtl;
}

.conference-side.west > * {
    direction: ltr;
}

.conference-center {
    display: grid;
    align-content: center;
    min-height: 100%;
}

.se-round {
    position: relative;
}

.se-round-stack {
    display: grid;
}

.final-stack {
    margin-top: 120px;
}

.round-title {
    font-size: 11px;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #475569;
    margin-bottom: 8px;
    padding-left: 4px;
}

.se-match,
.rr-match {
    position: relative;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    padding: 10px;
    display: grid;
    gap: 8px;
}

.conference-side.east .se-round:not(:last-child) .se-match::after {
    content: "";
    position: absolute;
    right: -18px;
    top: 50%;
    width: 18px;
    border-top: 2px solid #cbd5e1;
}

.conference-side.west .se-round:not(:last-child) .se-match::before {
    content: "";
    position: absolute;
    left: -18px;
    top: 50%;
    width: 18px;
    border-top: 2px solid #cbd5e1;
}

.conference-center .grand-final::before,
.conference-center .grand-final::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 16px;
    border-top: 2px solid #cbd5e1;
}

.conference-center .grand-final::before {
    left: -16px;
}

.conference-center .grand-final::after {
    right: -16px;
}

.match-head {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #64748b;
}

.fighter {
    width: 100%;
    text-align: left;
    border-radius: 8px;
    border: 1px solid transparent;
    padding: 9px 10px;
    transition: all 0.15s ease;
}

.fighter:disabled {
    cursor: default;
    opacity: 0.95;
}

.fighter-blue {
    background: #dbeafe;
    border-color: #93c5fd;
}

.fighter-blue:hover:not(:disabled) {
    background: #bfdbfe;
}

.fighter-green {
    background: #dcfce7;
    border-color: #86efac;
}

.fighter-green:hover:not(:disabled) {
    background: #bbf7d0;
}

.fighter.winner {
    box-shadow: 0 0 0 2px #0f172a inset;
    font-weight: 700;
}


.rr-board {
    display: grid;
    gap: 14px;
}

.rr-round {
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #fff;
    padding: 12px;
}

.rr-grid {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}

@media (max-width: 1100px) {
    .bracket-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
