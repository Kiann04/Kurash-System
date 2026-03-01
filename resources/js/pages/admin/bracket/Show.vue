<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Maximize2, Minimize2 } from 'lucide-vue-next'
import { ref, onMounted, onUnmounted } from 'vue'

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
    bronze_match?: MatchItem | null
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

const bracketSize = (entrants: number | undefined, totalRounds: number) => {
    if (entrants && entrants > 0) {
        let size = 1
        while (size < entrants) size *= 2
        return size
    }
    return Math.pow(2, totalRounds)
}

const roundLabel = (
    totalRounds: number,
    roundNumber: number,
    entrants?: number,
    format?: BracketItem['format']
) => {
    if (format !== 'single_elimination') {
        return `Round ${roundNumber}`
    }

    const size = bracketSize(entrants, totalRounds)
    const remaining = size / Math.pow(2, roundNumber - 1)
    if (remaining <= 2) return `Final (${remaining} -> ${remaining / 2})`
    if (remaining === 4) return 'Semifinal (4 -> 2)'
    if (remaining === 8) return 'Quarterfinal (8 -> 4)'
    if (remaining >= 16) return `Round of ${remaining} (${remaining} -> ${remaining / 2})`

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

const bronzeMatchFor = (bracket: BracketItem) => {
    return bracket.bronze_match ?? null
}

const isBye = (match: MatchItem) => {
    return (match.player_one_id === null && match.player_two_id !== null) ||
           (match.player_one_id !== null && match.player_two_id === null)
}

const chooseWinner = (match: MatchItem, winnerId: number | null) => {
    if (isCompleted) return

    // If it's a BYE, we can proceed even if winnerId is not explicitly passed,
    // though the UI currently only calls this with a valid player ID.
    if (!winnerId && !isBye(match)) return

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

const fullscreenBracketId = ref<number | null>(null)

const toggleFullScreen = (bracketId: number) => {
    const element = document.getElementById(`bracket-section-${bracketId}`)
    if (!element) return

    if (!document.fullscreenElement) {
        element.requestFullscreen().catch((err) => {
            console.error(`Error attempting to enable full-screen mode: ${err.message}`)
        })
    } else {
        document.exitFullscreen()
    }
}

const handleFullscreenChange = () => {
    if (!document.fullscreenElement) {
        fullscreenBracketId.value = null
    } else {
        const id = document.fullscreenElement.id
        if (id && id.startsWith('bracket-section-')) {
            fullscreenBracketId.value = parseInt(id.replace('bracket-section-', ''))
        }
    }
}

const activeBracketId = ref<number | null>(null)

const selectBracket = (id: number) => {
    activeBracketId.value = id
}

const clearSelection = () => {
    activeBracketId.value = null
}

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange)
})

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
})
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

            <!-- Brackets Summary Table -->
            <div v-if="props.brackets.length > 0 && !activeBracketId" class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Tournament Brackets</h2>
                    <span class="text-xs text-slate-500">{{ props.brackets.length }} Categories</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 border-b">
                            <tr>
                                <th class="text-left p-4 font-medium">Category</th>
                                <th class="text-center p-4 font-medium">Format</th>
                                <th class="text-center p-4 font-medium">Entrants</th>
                                <th class="text-left p-4 font-medium">Champion</th>
                                <th class="text-right p-4 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr 
                                v-for="bracket in props.brackets" 
                                :key="bracket.id" 
                                @click="selectBracket(bracket.id)"
                                class="hover:bg-blue-50/50 cursor-pointer transition-colors group"
                            >
                                <td class="p-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900 capitalize">{{ bracket.gender }} - {{ bracket.age_category }}</span>
                                        <span class="text-xs text-slate-500">{{ bracket.weight_category }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border" 
                                        :class="bracket.format === 'single_elimination' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-emerald-50 text-emerald-700 border-emerald-100'">
                                        {{ formatLabel(bracket.format) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center font-medium text-slate-700">
                                    {{ bracket.entrant_count ?? 0 }}
                                </td>
                                <td class="p-4">
                                    <div v-if="bracket.champion" class="flex items-center gap-1.5 text-amber-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                                        <span class="font-medium text-xs">{{ bracket.champion }}</span>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs italic">TBD</span>
                                </td>
                                <td class="p-4 text-right">
                                    <button class="text-blue-600 font-semibold text-xs hover:underline group-hover:translate-x-0.5 transition-transform inline-flex items-center gap-1">
                                        View Bracket
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active Bracket View -->
            <template v-if="activeBracketId">
                <div 
                    v-for="bracket in props.brackets.filter(b => b.id === activeBracketId)" 
                    :key="bracket.id" 
                    :id="`bracket-section-${bracket.id}`"
                    class="space-y-4 bracket-section"
                >
                    <div class="rounded-xl border border-slate-200 p-4 bg-white shadow-sm">
                        <div class="flex flex-wrap gap-2 text-sm items-center">
                            <button 
                                @click="clearSelection"
                                class="mr-2 p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 transition-colors flex items-center gap-1.5 px-3"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                <span class="text-xs font-semibold">Back to List</span>
                            </button>
                            <span class="tag font-bold">{{ (bracket.gender || 'unknown').toUpperCase() }}</span>
                            <span class="tag font-medium">{{ bracket.age_category || '-' }}</span>
                            <span class="tag font-medium">{{ bracket.weight_category || '-' }}</span>
                            <span class="tag font-medium">{{ formatLabel(bracket.format) }}</span>
                            <span class="tag font-medium">{{ bracket.entrant_count ?? 0 }} Entrants</span>
                            <span class="tag blue text-[10px] font-bold uppercase">Blue = upper</span>
                            <span class="tag green text-[10px] font-bold uppercase">Green = lower</span>
                            
                            <button 
                                @click="toggleFullScreen(bracket.id)" 
                                class="ml-auto p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-500 transition-colors print:hidden"
                                :title="fullscreenBracketId === bracket.id ? 'Exit Full Screen' : 'Full Screen'"
                            >
                                <component :is="fullscreenBracketId === bracket.id ? Minimize2 : Maximize2" class="size-4" />
                            </button>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-3 text-sm">
                            <div class="rounded-lg border border-amber-200 bg-amber-50/50 p-3 flex flex-col gap-1 shadow-sm">
                                <span class="text-[10px] uppercase font-bold text-amber-600 tracking-wider">Gold Medalist</span>
                                <span class="font-semibold text-slate-900">{{ safeAwards(bracket).gold || '-' }}</span>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50/50 p-3 flex flex-col gap-1 shadow-sm">
                                <span class="text-[10px] uppercase font-bold text-slate-600 tracking-wider">Silver Medalist</span>
                                <span class="font-semibold text-slate-900">{{ safeAwards(bracket).silver || '-' }}</span>
                            </div>
                            <div class="rounded-lg border border-orange-200 bg-orange-50/50 p-3 flex flex-col gap-1 shadow-sm">
                                <span class="text-[10px] uppercase font-bold text-orange-600 tracking-wider">Bronze Medalist</span>
                                <span class="font-semibold text-slate-900" v-if="safeAwards(bracket).bronze.length">{{ safeAwards(bracket).bronze.join(', ') }}</span>
                                <span class="font-semibold text-slate-900" v-else>-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bracket Board Renderers -->
                    <div v-if="bracket.format === 'single_elimination'" class="se-board">
                        <div class="conference-board">
                            <div class="conference-side east">
                                <div
                                    v-for="round in eastRounds(bracket)"
                                    :key="`east-${round.round}`"
                                    class="se-round"
                                    :style="roundColumnStyle(round.round)"
                                >
                                    <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, bracket.entrant_count, 'single_elimination') }}</h3>
                                    <div class="se-round-stack">
                                        <article v-for="match in round.matches" :key="`east-${match.id}`" class="se-match">
                                            <div class="match-head">
                                                <span>Match {{ match.match_number }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span v-if="isBye(match) && match.status !== 'completed'" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold animate-pulse">
                                                        BYE
                                                    </span>
                                                    <span>{{ match.status }}</span>
                                                </div>
                                            </div>

                                            <button
                                                 class="fighter fighter-blue"
                                                 :class="{ 'winner': match.winner_id === match.player_one_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                                 @click="chooseWinner(match, match.player_one_id)"
                                             >
                                                 {{ match.player_one || 'BYE' }}
                                             </button>

                                             <button
                                                 class="fighter fighter-green"
                                                 :class="{ 'winner': match.winner_id === match.player_two_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                                 @click="chooseWinner(match, match.player_two_id)"
                                             >
                                                 {{ match.player_two || 'BYE' }}
                                             </button>
                                        </article>
                                    </div>
                                </div>
                            </div>

                            <div class="conference-center">
                                <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), finalRoundNumber(bracket), bracket.entrant_count, 'single_elimination') }}</h3>
                                <div class="se-round-stack final-stack">
                                    <article
                                        v-for="match in grandFinalMatches(bracket)"
                                        :key="`final-${match.id}`"
                                        class="se-match grand-final"
                                    >
                                        <div class="match-head">
                                            <span>Final</span>
                                            <div class="flex items-center gap-2">
                                                <span v-if="isBye(match) && match.status !== 'completed'" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold animate-pulse">
                                                    BYE
                                                </span>
                                                <span>{{ match.status }}</span>
                                            </div>
                                        </div>
                                        <button
                                             class="fighter fighter-blue"
                                             :class="{ 'winner': match.winner_id === match.player_one_id }"
                                             :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                             @click="chooseWinner(match, match.player_one_id)"
                                         >
                                             {{ match.player_one || 'BYE' }}
                                         </button>
                                         <button
                                             class="fighter fighter-green"
                                             :class="{ 'winner': match.winner_id === match.player_two_id }"
                                             :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
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
                                    <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, bracket.entrant_count, 'single_elimination') }}</h3>
                                    <div class="se-round-stack">
                                        <article v-for="match in round.matches" :key="`west-${match.id}`" class="se-match">
                                            <div class="match-head">
                                                <span>Match {{ match.match_number }}</span>
                                                <div class="flex items-center gap-2">
                                                    <span v-if="isBye(match) && match.status !== 'completed'" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold animate-pulse">
                                                        BYE
                                                    </span>
                                                    <span>{{ match.status }}</span>
                                                </div>
                                            </div>

                                            <button
                                                 class="fighter fighter-blue"
                                                 :class="{ 'winner': match.winner_id === match.player_one_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                                 @click="chooseWinner(match, match.player_one_id)"
                                             >
                                                 {{ match.player_one || 'BYE' }}
                                             </button>

                                             <button
                                                 class="fighter fighter-green"
                                                 :class="{ 'winner': match.winner_id === match.player_two_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                                 @click="chooseWinner(match, match.player_two_id)"
                                             >
                                                 {{ match.player_two || 'BYE' }}
                                             </button>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="bronzeMatchFor(bracket)" class="bronze-board">
                            <h3 class="round-title">Bronze Match</h3>
                            <div class="se-round-stack">
                                <article class="se-match bronze-match">
                                    <div class="match-head">
                                        <span>Bronze Match</span>
                                        <div class="flex items-center gap-2">
                                            <span>{{ bronzeMatchFor(bracket)?.status }}</span>
                                        </div>
                                    </div>
                                    <button
                                         class="fighter fighter-blue"
                                         :class="{ 'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_one_id }"
                                         :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_one_id)"
                                         @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_one_id ?? null)"
                                     >
                                         {{ bronzeMatchFor(bracket)?.player_one || 'TBD' }}
                                     </button>
                                     <button
                                         class="fighter fighter-green"
                                         :class="{ 'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_two_id }"
                                         :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_two_id)"
                                         @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_two_id ?? null)"
                                     >
                                         {{ bronzeMatchFor(bracket)?.player_two || 'TBD' }}
                                     </button>
                                </article>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rr-board">
                        <div v-for="round in roundsForBracket(bracket)" :key="round.round" class="rr-round">
                            <h3 class="round-title">{{ roundLabel(finalRoundNumber(bracket), round.round, bracket.entrant_count, 'round_robin') }}</h3>
                            <div class="rr-grid">
                                <article v-for="match in round.matches" :key="match.id" class="rr-match">
                                    <div class="match-head">
                                        <span>Match {{ match.match_number }}</span>
                                        <div class="flex items-center gap-2">
                                            <span v-if="isBye(match) && match.status !== 'completed'" class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold animate-pulse">
                                                BYE
                                            </span>
                                            <span>{{ match.status }}</span>
                                        </div>
                                    </div>
                                    <button
                                         class="fighter fighter-blue"
                                         :class="{ 'winner': match.winner_id === match.player_one_id }"
                                         :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                         @click="chooseWinner(match, match.player_one_id)"
                                     >
                                         {{ match.player_one || 'BYE' }}
                                     </button>
                                     <button
                                         class="fighter fighter-green"
                                         :class="{ 'winner': match.winner_id === match.player_two_id }"
                                         :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                         @click="chooseWinner(match, match.player_two_id)"
                                     >
                                         {{ match.player_two || 'BYE' }}
                                     </button>
                                </article>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
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

.bracket-section:fullscreen {
    padding: 32px;
    background: #f8fafc;
    overflow-y: auto;
    overflow-x: hidden;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.bracket-section:fullscreen > * {
    width: 100%;
    max-width: 1800px;
}

.bracket-section:fullscreen .conference-board {
    min-width: auto;
    width: 100%;
    grid-template-columns: minmax(300px, 1fr) auto minmax(300px, 1fr);
    gap: 12px;
    padding: 20px 0;
    justify-items: center;
}

.bracket-section:fullscreen .conference-side {
    grid-auto-columns: minmax(140px, 1fr);
    gap: 10px;
    width: 100%;
    flex: 1;
}

.bracket-section:fullscreen .se-match,
.bracket-section:fullscreen .rr-match {
    padding: 6px;
    gap: 4px;
    border-radius: 8px;
}

.bracket-section:fullscreen .match-head {
    font-size: 10px;
}

.bracket-section:fullscreen .fighter {
    padding: 4px 6px;
    font-size: 11px;
}

.bracket-section:fullscreen .conference-center {
    width: 240px;
}

.bracket-section:fullscreen .final-stack {
    margin-top: 80px;
}

.bracket-section:fullscreen .round-title {
    font-size: 10px;
}

.bracket-section:fullscreen .tag {
    padding: 2px 8px;
    font-size: 11px;
}

.bracket-section:fullscreen .rr-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
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

.bronze-board {
    margin-top: 18px;
    display: grid;
    justify-items: center;
}

.bronze-board .bronze-match {
    border-color: #fdba74;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(251, 146, 60, 0.25);
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
    flex: 1;
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
