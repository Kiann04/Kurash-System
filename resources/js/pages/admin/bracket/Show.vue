<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Maximize2, Minimize2, Trophy, ArrowLeft, Download, Medal, AlertCircle, Info, User, Check, Calendar } from 'lucide-vue-next'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

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

const matchReady = (match: MatchItem) => {
    return match.player_one_id !== null && match.player_two_id !== null
}

const globalScheduledMatches = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        (br.matches ?? []).forEach((m) => {
            if (m.status === 'scheduled') {
                items.push({ ...m, bracket: br })
            }
        })
    })
    // Order: by round_number asc, then by bracket order (already sorted from backend), then match_number asc
    return items
        .sort((a, b) => {
            if (a.round_number !== b.round_number) return a.round_number - b.round_number
            if (a.bracket.id !== b.bracket.id) return a.bracket.id - b.bracket.id
            return a.match_number - b.match_number
        })
})

const globalCompletedMatches = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        (br.matches ?? []).forEach((m) => {
            if (m.status === 'completed') {
                items.push({ ...m, bracket: br })
            }
        })
    })
    // Most recent first by id as a simple proxy
    return items.sort((a, b) => b.id - a.id)
})

const tournamentMatchOrder = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        (br.matches ?? []).forEach((m) => {
            items.push({ ...m, bracket: br })
        })
    })
    // Order: by round_number asc, then by bracket order, then match_number asc
    return items.sort((a, b) => {
        if (a.round_number !== b.round_number) return a.round_number - b.round_number
        if (a.bracket.id !== b.bracket.id) return a.bracket.id - b.bracket.id
        return a.match_number - b.match_number
    })
})

const confirmAndChooseWinner = (match: MatchItem, winnerId: number | null) => {
    if (isCompleted) return
    if (!matchReady(match)) return
    if (!winnerId) return
    const p1 = match.player_one || 'BYE'
    const p2 = match.player_two || 'BYE'
    const chosen = winnerId === match.player_one_id ? p1 : p2
    if (!window.confirm(`Confirm winner?\n\n${p1} vs ${p2}\nWinner: ${chosen}`)) return
    chooseWinner(match, winnerId)
}

const revertMatch = (match: MatchItem) => {
    if (isCompleted) return
    if (!window.confirm('Revert this match result?')) return
    router.post(
        route('admin.tournaments.matches.revert', {
            tournament: props.tournament.id,
            match: match.id,
        }),
        {},
        { preserveScroll: true }
    )
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
            <div class="bracket-header print:hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ props.tournament.name }} Bracketing</h1>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground mt-1">
                        <span class="flex items-center gap-1">
                            <Calendar class="h-3.5 w-3.5" />
                            {{ props.tournament.tournament_date }}
                        </span>
                        <span class="text-xs">•</span>
                        <span class="capitalize">{{ props.tournament.status }}</span>
                        <span class="text-xs">•</span>
                        <span>Total Registered: {{ props.tournament.registrations_count ?? 0 }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        @click="exportPdf"
                    >
                        <Download class="h-4 w-4 mr-2" />
                        Export PDF
                    </Button>
                    <Button as-child variant="secondary">
                        <Link :href="route('admin.brackets.index')">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back
                        </Link>
                    </Button>
                </div>
            </div>
            <!-- Tournament Document (Match Order) -->
            <div class="tournament-document rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Tournament Document</h2>
                        <p class="text-xs text-slate-500">Match order for all categories.</p>
                    </div>
                    <Button variant="outline" class="print:hidden" @click="exportPdf">
                        <Download class="h-4 w-4 mr-2" />
                        Download PDF
                    </Button>
                </div>
                <div class="p-4 print:pt-0">
                    <div class="print-only mb-4">
                        <h1 class="text-lg font-bold">{{ props.tournament.name }}</h1>
                        <div class="text-xs text-slate-600">
                            {{ props.tournament.tournament_date }} - {{ props.tournament.status }}
                        </div>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="p-3 text-left">Order</th>
                                <th class="p-3 text-left">Category</th>
                                <th class="p-3 text-center">Round</th>
                                <th class="p-3 text-center">Match</th>
                                <th class="p-3 text-left">Player One</th>
                                <th class="p-3 text-left">Player Two</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(m, idx) in tournamentMatchOrder" :key="`doc-${m.id}`" class="border-t">
                                <td class="p-3 text-slate-500">{{ idx + 1 }}</td>
                                <td class="p-3">
                                    {{ (m.bracket.gender || 'unknown') }} - {{ m.bracket.age_category || '-' }} - {{ m.bracket.weight_category || '-' }}
                                </td>
                                <td class="p-3 text-center">{{ m.round_number }}</td>
                                <td class="p-3 text-center">
                                    {{ roundLabel(finalRoundNumber(m.bracket), m.round_number, m.bracket.entrant_count, m.bracket.format) }}
                                </td>
                                <td class="p-3">{{ m.player_one || 'BYE' }}</td>
                                <td class="p-3">{{ m.player_two || 'BYE' }}</td>
                                <td class="p-3 text-center capitalize">{{ m.status }}</td>
                            </tr>
                            <tr v-if="tournamentMatchOrder.length === 0">
                                <td colspan="7" class="p-4 text-center text-slate-500">No matches available.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Global Match List -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Match List (All Brackets)</h2>
                    <span class="text-xs text-slate-500">{{ globalScheduledMatches.length }} Scheduled</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Category</th>
                            <th class="p-3 text-center">Round</th>
                            <th class="p-3 text-center">Match</th>
                            <th class="p-3 text-left">Player One</th>
                            <th class="p-3 text-left">Player Two</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in globalScheduledMatches" :key="m.id" class="border-t">
                            <td class="p-3 text-slate-500">{{ m.id }}</td>
                            <td class="p-3">
                                {{ (m.bracket.gender || 'unknown') }} · {{ m.bracket.age_category || '-' }} · {{ m.bracket.weight_category || '-' }}
                            </td>
                            <td class="p-3 text-center">{{ m.round_number }}</td>
                            <td class="p-3 text-center">
                                {{ roundLabel(finalRoundNumber(m.bracket), m.round_number, m.bracket.entrant_count, m.bracket.format) }}
                            </td>
                            <td class="p-3">{{ m.player_one || 'BYE' }}</td>
                            <td class="p-3">{{ m.player_two || 'BYE' }}</td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                         class="fighter fighter-blue"
                                         :class="{ 'winner': m.winner_id === m.player_one_id }"
                                         :disabled="m.player_one_id === null || isCompleted"
                                         @click="confirmAndChooseWinner(m, m.player_one_id)"
                                     >
                                         {{ m.player_one || 'BYE' }}
                                     </button>
                                     <button
                                         class="fighter fighter-green"
                                         :class="{ 'winner': m.winner_id === m.player_two_id }"
                                         :disabled="m.player_two_id === null || isCompleted"
                                         @click="confirmAndChooseWinner(m, m.player_two_id)"
                                     >
                                         {{ m.player_two || 'BYE' }}
                                     </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="globalScheduledMatches.length === 0">
                            <td colspan="6" class="p-4 text-center text-slate-500">No scheduled matches.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Match History -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                <div class="p-4 border-b bg-slate-50/50 flex justify-between items-center">
                    <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Match History</h2>
                    <span class="text-xs text-slate-500">{{ globalCompletedMatches.length }} Completed</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="p-3 text-left">Category</th>
                            <th class="p-3 text-center">Round</th>
                            <th class="p-3 text-center">Match</th>
                            <th class="p-3 text-left">Winner</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in globalCompletedMatches" :key="`hist-${m.id}`" class="border-t">
                            <td class="p-3">
                                {{ (m.bracket.gender || 'unknown') }} · {{ m.bracket.age_category || '-' }} · {{ m.bracket.weight_category || '-' }}
                            </td>
                            <td class="p-3 text-center">{{ m.round_number }}</td>
                            <td class="p-3 text-center">M{{ m.match_number }}</td>
                            <td class="p-3">{{ m.winner || '—' }}</td>
                            <td class="p-3 text-center">
                                <button
                                    class="px-3 py-1 rounded border hover:bg-slate-50 disabled:opacity-50"
                                    :disabled="isCompleted"
                                    @click="revertMatch(m)"
                                >
                                    Revert
                                </button>
                            </td>
                        </tr>
                        <tr v-if="globalCompletedMatches.length === 0">
                            <td colspan="5" class="p-4 text-center text-slate-500">No completed matches.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Alert v-if="props.brackets.length === 0" variant="default">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>No Brackets</AlertTitle>
                <AlertDescription>
                    No brackets have been generated for this tournament yet.
                </AlertDescription>
            </Alert>

            <!-- Brackets Summary Table -->
            <Card v-if="props.brackets.length > 0 && !activeBracketId">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div class="space-y-1">
                        <CardTitle>Tournament Brackets</CardTitle>
                        <CardDescription>Select a category to view its bracket.</CardDescription>
                    </div>
                    <Badge variant="outline">{{ props.brackets.length }} Categories</Badge>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Category</TableHead>
                                <TableHead class="text-center">Format</TableHead>
                                <TableHead class="text-center">Entrants</TableHead>
                                <TableHead>Champion</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="bracket in props.brackets" 
                                :key="bracket.id" 
                                @click="selectBracket(bracket.id)"
                                class="cursor-pointer hover:bg-muted/50"
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
                    v-for="bracket in props.brackets.filter(b => b.id === activeBracketId)" 
                    :key="bracket.id" 
                    :id="`bracket-section-${bracket.id}`"
                    class="space-y-4 bracket-section"
                >
                    <Card class="shadow-sm">
                        <CardContent class="p-4">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <Button variant="outline" size="sm" @click="clearSelection">
                                        <ArrowLeft class="h-4 w-4 mr-2" />
                                        Back to List
                                    </Button>
                                    <div class="flex flex-wrap gap-2 items-center">
                                        <Badge variant="default">{{ (bracket.gender || 'unknown').toUpperCase() }}</Badge>
                                        <Badge variant="secondary">{{ bracket.age_category || '-' }}</Badge>
                                        <Badge variant="outline">{{ bracket.weight_category || '-' }}</Badge>
                                        <Badge variant="outline">{{ formatLabel(bracket.format) }}</Badge>
                                        <span class="text-sm text-muted-foreground ml-2">{{ bracket.entrant_count ?? 0 }} Entrants</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-2 mr-4 text-xs">
                                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-100 border border-blue-200 block"></span> Blue (Upper)</span>
                                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-100 border border-green-200 block"></span> Green (Lower)</span>
                                    </div>
                                    <Button variant="ghost" size="icon" @click="toggleFullScreen(bracket.id)" title="Full Screen">
                                        <component :is="fullscreenBracketId === bracket.id ? Minimize2 : Maximize2" class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4 md:grid-cols-3">
                                <Card class="bg-amber-50/50 border-amber-200">
                                    <CardContent class="p-3 flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                            <Medal class="h-5 w-5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs uppercase font-bold text-amber-600 tracking-wider">Gold</span>
                                            <span class="font-semibold text-slate-900">{{ safeAwards(bracket).gold || '-' }}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                                <Card class="bg-slate-50/50 border-slate-200">
                                    <CardContent class="p-3 flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600">
                                            <Medal class="h-5 w-5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs uppercase font-bold text-slate-600 tracking-wider">Silver</span>
                                            <span class="font-semibold text-slate-900">{{ safeAwards(bracket).silver || '-' }}</span>
                                        </div>
                                    </CardContent>
                                </Card>
                                <Card class="bg-orange-50/50 border-orange-200">
                                    <CardContent class="p-3 flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                            <Medal class="h-5 w-5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs uppercase font-bold text-orange-600 tracking-wider">Bronze</span>
                                            <span class="font-semibold text-slate-900" v-if="safeAwards(bracket).bronze.length">{{ safeAwards(bracket).bronze.join(', ') }}</span>
                                            <span class="font-semibold text-slate-900" v-else>-</span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Bracket Board Renderers -->
                    <div class="overflow-auto pb-6">
                        <template v-if="bracket.format === 'single_elimination'">
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
                                                    <span class="capitalize text-[10px] font-semibold" :class="match.status === 'completed' ? 'text-green-600' : 'text-slate-400'">{{ match.status }}</span>
                                                </div>
                                            </div>

                                            <button
                                                 class="fighter fighter-blue"
                                                 :class="{ 'winner': match.winner_id === match.player_one_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                                 @click="chooseWinner(match, match.player_one_id)"
                                             >
                                                <div class="flex items-center justify-between w-full">
                                                    <div class="flex items-center gap-2">
                                                        <User class="h-4 w-4 opacity-50" />
                                                        <span class="font-medium truncate">{{ match.player_one || 'BYE' }}</span>
                                                    </div>
                                                    <Check v-if="match.winner_id === match.player_one_id" class="h-4 w-4 text-blue-600" />
                                                </div>
                                             </button>

                                             <button
                                                 class="fighter fighter-green"
                                                 :class="{ 'winner': match.winner_id === match.player_two_id }"
                                                 :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                                 @click="chooseWinner(match, match.player_two_id)"
                                             >
                                                <div class="flex items-center justify-between w-full">
                                                    <div class="flex items-center gap-2">
                                                        <User class="h-4 w-4 opacity-50" />
                                                        <span class="font-medium truncate">{{ match.player_two || 'BYE' }}</span>
                                                    </div>
                                                    <Check v-if="match.winner_id === match.player_two_id" class="h-4 w-4 text-green-600" />
                                                </div>
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
                                                <span class="capitalize text-[10px] font-semibold" :class="match.status === 'completed' ? 'text-green-600' : 'text-slate-400'">{{ match.status }}</span>
                                            </div>
                                        </div>
                                        <button
                                             class="fighter fighter-blue"
                                             :class="{ 'winner': match.winner_id === match.player_one_id }"
                                             :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                             @click="chooseWinner(match, match.player_one_id)"
                                         >
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <User class="h-4 w-4 opacity-50" />
                                                    <span class="font-medium truncate">{{ match.player_one || 'BYE' }}</span>
                                                </div>
                                                <Check v-if="match.winner_id === match.player_one_id" class="h-4 w-4 text-blue-600" />
                                            </div>
                                         </button>
                                         <button
                                             class="fighter fighter-green"
                                             :class="{ 'winner': match.winner_id === match.player_two_id }"
                                             :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                             @click="chooseWinner(match, match.player_two_id)"
                                         >
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <User class="h-4 w-4 opacity-50" />
                                                    <span class="font-medium truncate">{{ match.player_two || 'BYE' }}</span>
                                                </div>
                                                <Check v-if="match.winner_id === match.player_two_id" class="h-4 w-4 text-green-600" />
                                            </div>
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
                                                    <span class="capitalize text-[10px] font-semibold" :class="match.status === 'completed' ? 'text-green-600' : 'text-slate-400'">{{ match.status }}</span>
                                                </div>
                                            </div>

                                            <button
                                             class="fighter fighter-blue"
                                             :class="{ 'winner': match.winner_id === match.player_one_id }"
                                             :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                             @click="chooseWinner(match, match.player_one_id)"
                                         >
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <User class="h-4 w-4 opacity-50" />
                                                    <span class="font-medium truncate">{{ match.player_one || 'BYE' }}</span>
                                                </div>
                                                <Check v-if="match.winner_id === match.player_one_id" class="h-4 w-4 text-blue-600" />
                                            </div>
                                         </button>
                                         <button
                                             class="fighter fighter-green"
                                             :class="{ 'winner': match.winner_id === match.player_two_id }"
                                             :disabled="isCompleted || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                             @click="chooseWinner(match, match.player_two_id)"
                                         >
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2">
                                                    <User class="h-4 w-4 opacity-50" />
                                                    <span class="font-medium truncate">{{ match.player_two || 'BYE' }}</span>
                                                </div>
                                                <Check v-if="match.winner_id === match.player_two_id" class="h-4 w-4 text-green-600" />
                                            </div>
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
                                            <span class="capitalize text-[10px] font-semibold" :class="bronzeMatchFor(bracket)?.status === 'completed' ? 'text-green-600' : 'text-slate-400'">{{ bronzeMatchFor(bracket)?.status }}</span>
                                        </div>
                                    </div>
                                    <button
                                         class="fighter fighter-blue"
                                         :class="{ 'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_one_id }"
                                         :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_one_id)"
                                         @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_one_id ?? null)"
                                     >
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <User class="h-4 w-4 opacity-50" />
                                                <span class="font-medium truncate">{{ bronzeMatchFor(bracket)?.player_one || 'TBD' }}</span>
                                            </div>
                                            <Check v-if="bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_one_id" class="h-4 w-4 text-blue-600" />
                                        </div>
                                     </button>
                                     <button
                                         class="fighter fighter-green"
                                         :class="{ 'winner': bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_two_id }"
                                         :disabled="isCompleted || (bronzeMatchFor(bracket)?.status === 'completed' && bronzeMatchFor(bracket)?.winner_id !== bronzeMatchFor(bracket)?.player_two_id)"
                                         @click="chooseWinner(bronzeMatchFor(bracket) as MatchItem, bronzeMatchFor(bracket)?.player_two_id ?? null)"
                                     >
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <User class="h-4 w-4 opacity-50" />
                                                <span class="font-medium truncate">{{ bronzeMatchFor(bracket)?.player_two || 'TBD' }}</span>
                                            </div>
                                            <Check v-if="bronzeMatchFor(bracket)?.winner_id === bronzeMatchFor(bracket)?.player_two_id" class="h-4 w-4 text-green-600" />
                                        </div>
                                     </button>
                                </article>
                            </div>
                        </div>
                        </template>
                        <template v-else>
                    <div class="rr-board">
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
                                            <span class="capitalize text-[10px] font-semibold" :class="match.status === 'completed' ? 'text-green-600' : 'text-slate-400'">{{ match.status }}</span>
                                        </div>
                                    </div>
                                    <button
                                         class="fighter fighter-blue"
                                         :class="{ 'winner': match.winner_id === match.player_one_id }"
                                         :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_one_id)"
                                         @click="chooseWinner(match, match.player_one_id)"
                                     >
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <User class="h-4 w-4 opacity-50" />
                                                <span class="font-medium truncate">{{ match.player_one || 'BYE' }}</span>
                                            </div>
                                            <Check v-if="match.winner_id === match.player_one_id" class="h-4 w-4 text-blue-600" />
                                        </div>
                                     </button>
                                     <button
                                         class="fighter fighter-green"
                                         :class="{ 'winner': match.winner_id === match.player_two_id }"
                                         :disabled="isCompleted || !matchReady(match) || (match.status === 'completed' && match.winner_id !== match.player_two_id)"
                                         @click="chooseWinner(match, match.player_two_id)"
                                     >
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2">
                                                <User class="h-4 w-4 opacity-50" />
                                                <span class="font-medium truncate">{{ match.player_two || 'BYE' }}</span>
                                            </div>
                                            <Check v-if="match.winner_id === match.player_two_id" class="h-4 w-4 text-green-600" />
                                        </div>
                                     </button>
                                </article>
                            </div>
                        </div>
                    </div>
                        </template>
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

.print-only {
    display: none;
}

@media print {
    .print-only {
        display: block;
    }

    .bracket-page > :not(.tournament-document) {
        display: none !important;
    }

    .tournament-document {
        border: none;
        box-shadow: none;
    }
}

@media (max-width: 1100px) {
    .bracket-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
