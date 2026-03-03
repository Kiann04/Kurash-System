<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { Maximize2, Minimize2, Trophy, ArrowLeft, Download, Medal, AlertCircle, Users, Check, Calendar, Crown, FileText, List, History, LayoutGrid } from 'lucide-vue-next'
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { route } from 'ziggy-js'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'

type TabType = 'document' | 'matches' | 'history' | 'brackets'
const activeTab = ref<TabType>('document')

interface MatchItem {
    id: number
    round_number: number
    match_number: number
    status: 'scheduled' | 'completed'
    player_one_id: number | null
    player_one: string | null
    player_one_seed?: number | null
    player_two_id: number | null
    player_two: string | null
    player_two_seed?: number | null
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

const isConfirmWinnerOpen = ref(false)
const confirmWinnerMatch = ref<MatchItem | null>(null)
const confirmWinnerId = ref<number | null>(null)

const isRevertMatchOpen = ref(false)
const revertMatchItem = ref<MatchItem | null>(null)

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
    // Base values
    const cardHeight = 74 // Updated height for new match card (2 fighters + borders)
    const baseGap = 32 // gap between matches in round 1

    if (roundNumber === 1) {
        return {
            marginTop: '0px',
            rowGap: `${baseGap}px`,
            '--row-gap': `${baseGap}px`
        }
    }

    // For subsequent rounds
    const power = Math.pow(2, roundNumber - 1)
    const marginTop = ((cardHeight + baseGap) * (power - 1)) / 2
    const gap = (cardHeight + baseGap) * power - cardHeight

    return {
        marginTop: `${marginTop}px`,
        rowGap: `${gap}px`,
        '--row-gap': `${gap}px`
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

    confirmWinnerMatch.value = match
    confirmWinnerId.value = winnerId
    isConfirmWinnerOpen.value = true
}

const submitConfirmWinner = () => {
    if (confirmWinnerMatch.value && confirmWinnerId.value) {
        chooseWinner(confirmWinnerMatch.value, confirmWinnerId.value)
    }
    isConfirmWinnerOpen.value = false
}

const revertMatch = (match: MatchItem) => {
    if (isCompleted) return
    revertMatchItem.value = match
    isRevertMatchOpen.value = true
}

const submitRevertMatch = () => {
    if (revertMatchItem.value) {
        router.post(
            route('admin.tournaments.matches.revert', {
                tournament: props.tournament.id,
                match: revertMatchItem.value.id,
            }),
            {},
            { preserveScroll: true }
        )
    }
    isRevertMatchOpen.value = false
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

const getStatusClass = (status: string) => {
    switch (status) {
        case 'draft':
            return 'bg-yellow-100 text-yellow-700 border-yellow-200 hover:bg-yellow-100/80 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800'
        case 'open':
            return 'bg-green-100 text-green-700 border-green-200 hover:bg-green-100/80 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800'
        case 'ongoing':
            return 'bg-blue-100 text-blue-700 border-blue-200 hover:bg-blue-100/80 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800'
        case 'completed':
            return 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-100/80 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-100/80 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
    }
}

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
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
        <div class="flex-1 flex flex-col min-h-screen bg-slate-50 dark:bg-slate-950">
            <!-- Header -->
            <div class="px-6 py-6 border-b bg-white dark:bg-slate-900 dark:border-slate-800">
                <div class="bracket-header print:hidden flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ props.tournament.name }} Bracketing</h1>
                        <div class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground mt-1">
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3.5 w-3.5" />
                                {{ props.tournament.tournament_date }}
                            </span>
                            <span class="hidden sm:inline">•</span>
                            <Badge variant="outline" :class="['capitalize', getStatusClass(props.tournament.status)]">
                                {{ props.tournament.status }}
                            </Badge>
                            <span class="hidden sm:inline">•</span>
                            <span class="flex items-center gap-1">
                                <Users class="h-3.5 w-3.5" />
                                Total Registered: {{ props.tournament.registrations_count ?? 0 }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            @click="exportPdf"
                            class="dark:bg-slate-800 dark:text-slate-100 dark:border-slate-700 dark:hover:bg-slate-700"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                        <Button as-child variant="secondary" class="dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                            <Link :href="route('admin.brackets.index')">
                                <ArrowLeft class="h-4 w-4 mr-2" />
                                Back
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="flex items-center gap-1 mt-8 border-b border-transparent">
                    <button 
                        @click="activeTab = 'document'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                        :class="activeTab === 'document' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    >
                        <FileText class="h-4 w-4" />
                        Tournament Document
                    </button>
                    <button 
                        @click="activeTab = 'matches'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                        :class="activeTab === 'matches' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    >
                        <List class="h-4 w-4" />
                        Match List
                    </button>
                    <button 
                        @click="activeTab = 'history'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                        :class="activeTab === 'history' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    >
                        <History class="h-4 w-4" />
                        Match History
                    </button>
                    <button 
                        @click="activeTab = 'brackets'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-2"
                        :class="activeTab === 'brackets' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    >
                        <LayoutGrid class="h-4 w-4" />
                        Tournament Bracket
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Tournament Document (Match Order) -->
                <div v-show="activeTab === 'document'">
                    <Card class="tournament-document overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
                <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 flex flex-row items-center justify-between space-y-0 py-4">
                    <div class="space-y-1">
                        <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                            Tournament Document
                        </CardTitle>
                        <CardDescription>Match order for all categories.</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" class="print:hidden dark:border-slate-700" @click="exportPdf">
                        <Download class="h-4 w-4 mr-2" />
                        Download PDF
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="print-only mb-4 p-4">
                        <h1 class="text-lg font-bold">{{ props.tournament.name }}</h1>
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            {{ props.tournament.tournament_date }} - {{ props.tournament.status }}
                        </div>
                    </div>
                    <div class="max-h-100 overflow-auto">
                        <Table>
                            <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm">
                                <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                    <TableHead class="w-16 font-semibold text-slate-500 dark:text-slate-400">Order</TableHead>
                                    <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Category</TableHead>
                                    <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Round</TableHead>
                                    <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Match</TableHead>
                                    <TableHead class="font-semibold text-slate-500 dark:text-slate-400 w-1/4">Player One</TableHead>
                                    <TableHead class="font-semibold text-slate-500 dark:text-slate-400 w-1/4">Player Two</TableHead>
                                    <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="(m, idx) in tournamentMatchOrder" 
                                    :key="`doc-${m.id}`"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800 group"
                                >
                                    <TableCell class="text-muted-foreground font-mono text-xs">{{ idx + 1 }}</TableCell>
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ m.bracket.gender }}</span>
                                            <span class="text-xs text-muted-foreground">{{ m.bracket.age_category }} · {{ m.bracket.weight_category }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 font-mono text-xs">
                                            {{ m.round_number }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="secondary" class="font-normal text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border-transparent">
                                            {{ roundLabel(finalRoundNumber(m.bracket), m.round_number, m.bracket.entrant_count, m.bracket.format) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <template v-if="m.player_one">
                                                <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-700">
                                                    <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_one}&background=random`" />
                                                    <AvatarFallback>{{ getInitials(m.player_one) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                                        {{ m.player_one }}
                                                        <Crown v-if="m.winner_id === m.player_one_id" class="h-3 w-3 text-yellow-500 fill-yellow-500" />
                                                    </span>
                                                </div>
                                            </template>
                                            <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-3">
                                            <template v-if="m.player_two">
                                                <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-700">
                                                    <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_two}&background=random`" />
                                                    <AvatarFallback>{{ getInitials(m.player_two) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-sm text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                                                        {{ m.player_two }}
                                                        <Crown v-if="m.winner_id === m.player_two_id" class="h-3 w-3 text-yellow-500 fill-yellow-500" />
                                                    </span>
                                                </div>
                                            </template>
                                            <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge 
                                            :variant="m.status === 'completed' ? 'default' : 'outline'" 
                                            class="capitalize shadow-none"
                                            :class="{
                                                'bg-green-600 hover:bg-green-700 text-white border-green-600': m.status === 'completed',
                                                'text-slate-500 border-slate-200 bg-white dark:bg-slate-950 dark:border-slate-800 dark:text-slate-400': m.status === 'scheduled'
                                            }"
                                        >
                                            {{ m.status }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="tournamentMatchOrder.length === 0">
                                    <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                        No matches available.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
            </div>

            <!-- Global Match List -->
            <div v-show="activeTab === 'matches'">
            <Card class="overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
                <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                                Match List (All Brackets)
                            </CardTitle>
                            <CardDescription>Manage ongoing and upcoming matches</CardDescription>
                        </div>
                        <Badge variant="secondary" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 shadow-sm">{{ globalScheduledMatches.length }} Scheduled</Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10">
                            <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                <TableHead class="w-16 font-semibold text-slate-500 dark:text-slate-400">ID</TableHead>
                                <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Category</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Round</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Match</TableHead>
                                <TableHead class="font-semibold text-slate-500 dark:text-slate-400 w-1/4">Player One</TableHead>
                                <TableHead class="font-semibold text-slate-500 dark:text-slate-400 w-1/4">Player Two</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="m in globalScheduledMatches" 
                                :key="m.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800"
                            >
                                <TableCell class="text-muted-foreground font-mono text-xs">{{ m.id }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ m.bracket.gender }}</span>
                                        <span class="text-xs text-muted-foreground">{{ m.bracket.age_category }} · {{ m.bracket.weight_category }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 font-mono text-xs">
                                        {{ m.round_number }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="secondary" class="font-normal text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border-transparent">
                                        {{ roundLabel(finalRoundNumber(m.bracket), m.round_number, m.bracket.entrant_count, m.bracket.format) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-3">
                                        <template v-if="m.player_one">
                                            <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-700">
                                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_one}&background=random`" />
                                                <AvatarFallback>{{ getInitials(m.player_one) }}</AvatarFallback>
                                            </Avatar>
                                            <span class="text-sm text-slate-900 dark:text-slate-100">{{ m.player_one }}</span>
                                        </template>
                                        <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                    </div>
                                </TableCell>
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-3">
                                        <template v-if="m.player_two">
                                            <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-700">
                                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_two}&background=random`" />
                                                <AvatarFallback>{{ getInitials(m.player_two) }}</AvatarFallback>
                                            </Avatar>
                                            <span class="text-sm text-slate-900 dark:text-slate-100">{{ m.player_two }}</span>
                                        </template>
                                        <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                             size="sm"
                                             :variant="m.winner_id === m.player_one_id ? 'default' : 'outline'"
                                             class="w-full sm:w-auto h-8 text-xs dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                                             :class="{ 
                                                'bg-green-600 hover:bg-green-700 text-white dark:bg-green-600 dark:hover:bg-green-700 border-green-600': m.winner_id === m.player_one_id,
                                                'hover:border-green-500 hover:text-green-600 dark:hover:text-green-400': m.winner_id !== m.player_one_id && !isCompleted && m.player_one_id
                                             }"
                                             :disabled="m.player_one_id === null || isCompleted"
                                             @click="confirmAndChooseWinner(m, m.player_one_id)"
                                         >
                                             <User class="h-3 w-3 mr-1.5 opacity-70" />
                                             <span class="truncate max-w-25">{{ m.player_one ? 'Select P1' : 'BYE' }}</span>
                                             <Check v-if="m.winner_id === m.player_one_id" class="ml-1.5 h-3 w-3" />
                                         </Button>
                                         <span class="text-muted-foreground text-xs font-bold uppercase">vs</span>
                                         <Button
                                             size="sm"
                                             :variant="m.winner_id === m.player_two_id ? 'default' : 'outline'"
                                             class="w-full sm:w-auto h-8 text-xs dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800"
                                             :class="{ 
                                                'bg-green-600 hover:bg-green-700 text-white dark:bg-green-600 dark:hover:bg-green-700 border-green-600': m.winner_id === m.player_two_id,
                                                'hover:border-green-500 hover:text-green-600 dark:hover:text-green-400': m.winner_id !== m.player_two_id && !isCompleted && m.player_two_id
                                             }"
                                             :disabled="m.player_two_id === null || isCompleted"
                                             @click="confirmAndChooseWinner(m, m.player_two_id)"
                                         >
                                             <User class="h-3 w-3 mr-1.5 opacity-70" />
                                             <span class="truncate max-w-25">{{ m.player_two ? 'Select P2' : 'BYE' }}</span>
                                             <Check v-if="m.winner_id === m.player_two_id" class="ml-1.5 h-3 w-3" />
                                         </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="globalScheduledMatches.length === 0">
                                <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                    No scheduled matches.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            </div>

            <!-- Match History -->
            <div v-show="activeTab === 'history'">
            <Card class="overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
                <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4">
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                                Match History
                            </CardTitle>
                            <CardDescription>Recently completed matches</CardDescription>
                        </div>
                        <Badge variant="secondary" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 shadow-sm">{{ globalCompletedMatches.length }} Completed</Badge>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm">
                            <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Category</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Round</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Match</TableHead>
                                <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Winner</TableHead>
                                <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow 
                                v-for="m in globalCompletedMatches" 
                                :key="`hist-${m.id}`"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800"
                            >
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ m.bracket.gender }}</span>
                                        <span class="text-xs text-muted-foreground">{{ m.bracket.age_category }} · {{ m.bracket.weight_category }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 font-mono text-xs">
                                        {{ m.round_number }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="secondary" class="font-normal text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 border-transparent">
                                        M{{ m.match_number }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Trophy class="h-4 w-4 text-amber-500 fill-amber-500" />
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ m.winner || '—' }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="h-8 text-xs hover:bg-destructive hover:text-destructive-foreground dark:border-slate-700 dark:hover:bg-destructive/80"
                                        :disabled="isCompleted"
                                        @click="revertMatch(m)"
                                    >
                                        Revert
                                    </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="globalCompletedMatches.length === 0">
                                <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                    No completed matches.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
            </div>

            <div v-show="activeTab === 'brackets'">
            <Alert v-if="props.brackets.length === 0" variant="default">
                <AlertCircle class="h-4 w-4" />
                <AlertTitle>No Brackets</AlertTitle>
                <AlertDescription>
                    No brackets have been generated for this tournament yet.
                </AlertDescription>
            </Alert>

            <!-- Brackets Summary Table -->
            <Card v-if="props.brackets.length > 0 && !activeBracketId" class="overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
                <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4 flex flex-row items-center justify-between space-y-0">
                    <div class="space-y-1">
                        <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                            Tournament Brackets
                        </CardTitle>
                        <CardDescription>Select a category to view its bracket.</CardDescription>
                    </div>
                    <Badge variant="outline" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 shadow-sm">{{ props.brackets.length }} Categories</Badge>
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
                                v-for="bracket in props.brackets" 
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
                    v-for="bracket in props.brackets.filter(b => b.id === activeBracketId)" 
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
        </div>
        </div>
        
        <!-- Confirm Winner Dialog -->
        <Dialog :open="isConfirmWinnerOpen" @update:open="isConfirmWinnerOpen = $event">
            <DialogContent class="sm:max-w-md dark:bg-slate-950 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="dark:text-slate-100">Confirm Match Winner</DialogTitle>
                    <DialogDescription class="dark:text-slate-400">
                        Are you sure you want to declare this player as the winner?
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <div class="rounded-md border p-4 bg-muted/20 dark:bg-slate-900/50 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium dark:text-slate-300">Match Details</span>
                            <Badge variant="outline" class="dark:border-slate-700 dark:text-slate-400">
                                Match #{{ confirmWinnerMatch?.match_number }}
                            </Badge>
                        </div>
                        <div class="grid gap-2 text-sm">
                            <div class="flex justify-between items-center p-2 rounded" :class="confirmWinnerId === confirmWinnerMatch?.player_one_id ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-semibold' : 'text-muted-foreground'">
                                <span>{{ confirmWinnerMatch?.player_one || 'BYE' }}</span>
                                <Check v-if="confirmWinnerId === confirmWinnerMatch?.player_one_id" class="h-4 w-4" />
                            </div>
                            <div class="text-center text-xs text-muted-foreground">VS</div>
                            <div class="flex justify-between items-center p-2 rounded" :class="confirmWinnerId === confirmWinnerMatch?.player_two_id ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-semibold' : 'text-muted-foreground'">
                                <span>{{ confirmWinnerMatch?.player_two || 'BYE' }}</span>
                                <Check v-if="confirmWinnerId === confirmWinnerMatch?.player_two_id" class="h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isConfirmWinnerOpen = false" class="dark:border-slate-800 dark:text-slate-300">
                        Cancel
                    </Button>
                    <Button @click="submitConfirmWinner" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700">
                        Confirm Winner
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Revert Match Dialog -->
        <Dialog :open="isRevertMatchOpen" @update:open="isRevertMatchOpen = $event">
            <DialogContent class="sm:max-w-md dark:bg-slate-950 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-destructive dark:text-red-500">
                        <AlertCircle class="h-5 w-5" />
                        Revert Match Result
                    </DialogTitle>
                    <DialogDescription class="dark:text-slate-400">
                        Are you sure you want to revert the result of this match? This will clear the winner and reset the match status.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4" v-if="revertMatchItem">
                    <div class="rounded-md border p-4 bg-muted/20 dark:bg-slate-900/50 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium dark:text-slate-300">Match #{{ revertMatchItem.match_number }}</span>
                            <Badge variant="outline" class="dark:border-slate-700 dark:text-slate-400">
                                Round {{ revertMatchItem.round_number }}
                            </Badge>
                        </div>
                        <div class="text-sm text-center py-2 text-muted-foreground">
                            {{ revertMatchItem.player_one || 'BYE' }} vs {{ revertMatchItem.player_two || 'BYE' }}
                        </div>
                        <div class="mt-2 text-center text-sm font-medium text-green-600 dark:text-green-400">
                            Current Winner: {{ revertMatchItem.winner || 'None' }}
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="isRevertMatchOpen = false" class="dark:border-slate-800 dark:text-slate-300">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="submitRevertMatch">
                        <History class="mr-2 h-4 w-4" />
                        Revert Match
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        
        <!-- Floating Exit Fullscreen Button -->
        <div v-if="fullscreenBracketId" class="fixed bottom-6 right-6 z-50">
             <Button 
                variant="secondary" 
                size="lg" 
                class="shadow-lg border border-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-700"
                @click="toggleFullScreen(fullscreenBracketId!)"
            >
                <Minimize2 class="mr-2 h-4 w-4" />
                Exit Full Screen
            </Button>
        </div>
    </AppLayout>
</template>

<style lang="postcss" scoped>
/* Round title styling */
.round-title {
    @apply text-sm font-bold text-slate-500 uppercase tracking-widest mb-6 text-center;
}
.dark .round-title {
    @apply text-slate-400;
}

/* Match card styling */
.se-match-container {
    @apply relative flex items-center gap-4;
}

.match-number-left {
    @apply text-xs font-black text-slate-400 w-5 text-right;
}

.se-match {
    @apply flex flex-col w-full relative z-10;
    min-width: 220px;
}

.match-card-body {
    @apply bg-white border border-slate-200 rounded-md overflow-hidden shadow-sm transition-all duration-200 relative;
    height: 74px; /* Fixed height for consistent layout and connector math */
}

.dark .match-card-body {
    @apply bg-slate-900 border-slate-800 shadow-md;
}

/* Fighter button styling */
.fighter {
    @apply flex flex-col w-full p-0 text-left transition-all duration-200 relative;
}

.fighter-inner {
    @apply flex items-stretch h-9;
}

.fighter-color-indicator {
    @apply w-1.5 shrink-0;
}

.fighter-blue .fighter-color-indicator {
    @apply bg-blue-600;
}

.fighter-green .fighter-color-indicator {
    @apply bg-emerald-600;
}

.fighter-name {
    @apply flex items-center flex-1 px-3 text-slate-700 text-xs font-semibold;
}

.fighter-blue .fighter-name {
    @apply bg-blue-50/50;
}

.fighter-green .fighter-name {
    @apply bg-emerald-50/50;
}

.dark .fighter-name {
    @apply text-slate-300;
}

.dark .fighter-blue .fighter-name {
    @apply bg-blue-900/10;
}

.dark .fighter-green .fighter-name {
    @apply bg-emerald-900/10;
}

/* Winner state */
.fighter.winner .fighter-name {
    @apply font-bold;
}

.fighter-blue.winner .fighter-name {
    @apply bg-blue-600 text-white;
}
.dark .fighter-blue.winner .fighter-name {
    @apply bg-blue-600 text-white;
}

.fighter-green.winner .fighter-name {
    @apply bg-emerald-600 text-white;
}
.dark .fighter-green.winner .fighter-name {
    @apply bg-emerald-600 text-white;
}

.fighter-blue:not(.winner):not(:disabled):hover .fighter-name {
    @apply bg-blue-100/50;
}
.dark .fighter-blue:not(.winner):not(:disabled):hover .fighter-name {
    @apply bg-blue-900/30;
}

.fighter-green:not(.winner):not(:disabled):hover .fighter-name {
    @apply bg-emerald-100/50;
}
.dark .fighter-green:not(.winner):not(:disabled):hover .fighter-name {
    @apply bg-emerald-900/30;
}

/* Bracket connection lines (Standard Fork Shape) */
.standard-bracket .se-round:not(:last-child) .match-card-body::after {
    content: "";
    @apply absolute -right-6 top-1/2 w-6 border-t-2 border-slate-300;
}
.dark .standard-bracket .se-round:not(:last-child) .match-card-body::after {
    @apply border-slate-700;
}

/* Connector Left for rounds after the first */
.standard-bracket .se-round:not(:first-child) .match-card-body::before {
    content: "";
    @apply absolute -left-6 top-1/2 w-6 border-t-2 border-slate-300;
}
.dark .standard-bracket .se-round:not(:first-child) .match-card-body::before {
    @apply border-slate-700;
}

/* Vertical line Down for Odd matches */
.standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(odd)::after {
    content: "";
    @apply absolute -right-6 w-0 border-r-2 border-slate-300;
    top: 50%;
    height: calc(50% + (var(--row-gap) / 2));
    z-index: 0;
}
.dark .standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(odd)::after {
    @apply border-slate-700;
}

/* Vertical line Up for Even matches */
.standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(even)::after {
    content: "";
    @apply absolute -right-6 w-0 border-r-2 border-slate-300;
    bottom: 50%;
    height: calc(50% + (var(--row-gap) / 2));
    z-index: 0;
}
.dark .standard-bracket .se-round:not(:last-child) .se-match-container:nth-child(even)::after {
    @apply border-slate-700;
}

/* Match Labels (Optional - hiding for now to match clean look or enabling if needed) */
/* If we want match numbers on the connectors */

/* Round title styling in full screen */
.bracket-section:fullscreen .round-title {
    @apply mb-10 text-base;
}

.bracket-section:fullscreen .se-match, 
.bracket-section:fullscreen .rr-match-container {
    @apply shadow-lg;
    min-width: 280px;
}

/* Round Robin styling */
.rr-board {
    @apply grid gap-8;
}

.rr-round {
    @apply border border-slate-200 rounded-xl bg-white p-6 shadow-sm;
}
.dark .rr-round {
    @apply bg-slate-900 border-slate-800;
}

.rr-grid {
    @apply grid gap-6 grid-cols-[repeat(auto-fill,minmax(240px,1fr))];
}

.bracket-section:fullscreen .rr-grid {
    @apply grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6;
}

/* Print and Utility styling */
.print-only {
    @apply hidden;
}

@media print {
    .print-only {
        @apply block;
    }

    .bracket-page > :not(.tournament-document) {
        @apply hidden !important;
    }

    .tournament-document {
        @apply border-none shadow-none;
    }
}

@media (max-width: 1100px) {
    .bracket-header {
        @apply flex-col items-start gap-3;
    }
}
</style>
