<script setup lang="ts">
/**
 * Bracket Management Dashboard
 * 
 * The central hub for managing a tournament's brackets.
 * Features a tabbed interface for:
 * - Tournament Document (Overall summary)
 * - Match List (Scheduled matches)
 * - Match History (Completed matches)
 * - Brackets View (Visual bracket representation)
 */
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Trophy, Calendar, Users, List, History, LayoutGrid, FileText, AlertCircle } from 'lucide-vue-next'
import { ref, computed } from 'vue'
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
import { useBracketLogic } from '@/composables/useBracketLogic'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import type { MatchItem, BracketItem, TournamentItem, CategoryParticipant } from '@/types/bracket'

// Components
import BracketView from './components/BracketView.vue'
import MatchHistory from './components/MatchHistory.vue'
import MatchList from './components/MatchList.vue'
import TournamentDocument from './components/TournamentDocument.vue'

type TabType = 'document' | 'matches' | 'history' | 'brackets'

/**
 * Props received from the Inertia controller
 * @property tournament Tournament details
 * @property category_participants List of participants grouped by category (optional)
 * @property brackets List of bracket data including matches
 */
const props = defineProps<{
    tournament: TournamentItem
    category_participants?: CategoryParticipant[]
    brackets: BracketItem[]
}>()

// Navigation and View State
const activeTab = ref<TabType>('document')
const activeBracketId = ref<number | null>(null)
const fullscreenBracketId = ref<number | null>(null)

/**
 * Computed flag to check if the tournament is completed
 */
const isCompleted = computed(() => props.tournament.status === 'completed')

// Modals state
const isConfirmWinnerOpen = ref(false)
const confirmWinnerMatch = ref<MatchItem | null>(null)
const confirmWinnerId = ref<number | null>(null)

const isRevertMatchOpen = ref(false)
const revertMatchItem = ref<MatchItem | null>(null)

// Logic from composable
const { 
    matchReady 
} = useBracketLogic()

/**
 * Breadcrumbs
 */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: 'Brackets', href: route('admin.brackets.index') },
    { title: 'Bracket Management', href: '' },
]

/**
 * Computed list of all scheduled matches across all brackets.
 * Filters for matches that are not completed and are ready to be played.
 */
const globalScheduledMatches = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        const matches = br.matches ?? []
        matches.forEach((m) => {
            if (m.status !== 'completed' && matchReady(m)) {
                items.push({ ...m, bracket: br })
            }
        })
    })
    return items.sort((a, b) => a.id - b.id)
})

/**
 * Computed list of all completed matches (history).
 * Includes bronze matches if completed.
 * Sorted by most recent first (descending ID).
 */
const globalMatchHistory = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        const matches = br.matches ?? []
        matches.forEach((m) => {
            if (m.status === 'completed') {
                items.push({ ...m, bracket: br })
            }
        })
        if (br.bronze_match && br.bronze_match.status === 'completed') {
             items.push({ ...br.bronze_match, bracket: br })
        }
    })
    return items.sort((a, b) => b.id - a.id) // Recent first
})

/**
 * Computed list of ALL matches regardless of status.
 * Used for the Tournament Document view.
 */
const allMatches = computed(() => {
    const items: Array<MatchItem & { bracket: BracketItem }> = []
    props.brackets.forEach((br) => {
        const matches = br.matches ?? []
        matches.forEach((m) => {
            items.push({ ...m, bracket: br })
        })
        if (br.bronze_match) {
             items.push({ ...br.bronze_match, bracket: br })
        }
    })
    return items.sort((a, b) => a.id - b.id)
})

/**
 * Selects a specific bracket to view and scrolls to it.
 * 
 * @param id Bracket ID
 */
const selectBracket = (id: number) => {
    activeBracketId.value = id
    // Scroll to the bracket section
    setTimeout(() => {
        const el = document.getElementById(`bracket-section-${id}`)
        if (el) el.scrollIntoView({ behavior: 'smooth' })
    }, 100)
}

/**
 * Clears the currently selected bracket highlight.
 */
const clearSelection = () => {
    activeBracketId.value = null
}

/**
 * Toggles fullscreen mode for a specific bracket.
 * 
 * @param id Bracket ID
 */
const toggleFullScreen = (id: number) => {
    if (fullscreenBracketId.value === id) {
        fullscreenBracketId.value = null
    } else {
        fullscreenBracketId.value = id
    }
}

/**
 * Opens the winner confirmation dialog for a match.
 * 
 * @param match The match object
 * @param winnerId The ID of the winning player
 */
const openConfirmWinner = (match: MatchItem, winnerId: number | null) => {
    if (isCompleted.value) return
    if (!winnerId) return

    confirmWinnerMatch.value = match
    confirmWinnerId.value = winnerId
    isConfirmWinnerOpen.value = true
}

const closeConfirmWinner = () => {
    isConfirmWinnerOpen.value = false
    confirmWinnerMatch.value = null
    confirmWinnerId.value = null
}

/**
 * Submits the winner confirmation to the server.
 * Advances the winner to the next round.
 */
const confirmWinner = () => {
    if (!confirmWinnerMatch.value || !confirmWinnerId.value) return

    router.post(
        route('admin.tournaments.matches.advance', { tournament: props.tournament.id, match: confirmWinnerMatch.value.id }),
        { winner_id: confirmWinnerId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeConfirmWinner()
            },
        }
    )
}

/**
 * Opens the revert match dialog.
 * 
 * @param match The match object to revert
 */
const openRevertMatch = (match: MatchItem) => {
    if (isCompleted.value) return
    revertMatchItem.value = match
    isRevertMatchOpen.value = true
}

const closeRevertMatch = () => {
    isRevertMatchOpen.value = false
    revertMatchItem.value = null
}

/**
 * Submits the revert request to the server.
 * Resets the match status and clears the winner.
 */
const revertMatch = () => {
    if (!revertMatchItem.value) return

    router.post(
        route('admin.tournaments.matches.revert', { tournament: props.tournament.id, match: revertMatchItem.value.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRevertMatch()
            },
        }
    )
}

/**
 * Navigates back to the tournament list.
 */
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back()
    } else {
        router.visit(route('admin.tournaments.index'))
    }
}
</script>

<template>
    <AppLayout title="Bracket Management" :breadcrumbs="pageBreadcrumbs">
        <Head title="Bracket Management" />

        <div class="py-6" :class="{ 'fixed inset-0 z-50 bg-background overflow-auto p-4': fullscreenBracketId }">
            <div :class="{ 'max-w-360 mx-auto sm:px-4 lg:px-6': !fullscreenBracketId, 'h-full': fullscreenBracketId }">
                <!-- Header (Hidden in Fullscreen) -->
                <div v-if="!fullscreenBracketId" class="mb-6">
                    <Button variant="ghost" class="mb-4 pl-0 hover:bg-transparent hover:text-primary" @click="goBack">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Back to Tournaments
                    </Button>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-3">
                                <Trophy class="h-8 w-8 text-primary" />
                                {{ tournament.name }}
                            </h1>
                            <div class="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    {{ new Date(tournament.tournament_date).toLocaleDateString() }}
                                </span>
                                <Badge :variant="tournament.status === 'completed' ? 'secondary' : 'default'">
                                    {{ tournament.status.toUpperCase() }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="space-y-6">
                    <!-- Navigation Tabs (Hidden in Fullscreen) -->
                    <Card v-if="!fullscreenBracketId" class="border-b rounded-none shadow-none border-0 bg-transparent">
                        <div class="flex items-center gap-1 overflow-x-auto pb-2">
                            <Button
                                v-for="tab in ['document', 'matches', 'history', 'brackets']"
                                :key="tab"
                                :variant="activeTab === tab ? 'default' : 'ghost'"
                                class="rounded-full px-4 h-9 font-medium transition-all"
                                :class="activeTab === tab ? 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-md' : 'text-muted-foreground hover:bg-muted'"
                                @click="activeTab = tab as TabType"
                            >
                                <component
                                    :is="{
                                        document: FileText,
                                        matches: List,
                                        history: History,
                                        brackets: LayoutGrid
                                    }[tab]"
                                    class="w-4 h-4 mr-2"
                                />
                                {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
                            </Button>
                        </div>
                    </Card>

                    <!-- Tab Content -->
                    <div class="min-h-125">
                        
                        <!-- Document Tab -->
                        <div v-if="activeTab === 'document' && !fullscreenBracketId" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <TournamentDocument 
                                :tournament="tournament"
                                :matches="allMatches"
                            />
                        </div>

                        <!-- Matches Tab -->
                        <div v-if="activeTab === 'matches' && !fullscreenBracketId" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <MatchList 
                                :matches="globalScheduledMatches"
                                :is-completed="isCompleted"
                                @confirmWinner="openConfirmWinner"
                            />
                        </div>

                        <!-- History Tab -->
                        <div v-if="activeTab === 'history' && !fullscreenBracketId" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <MatchHistory 
                                :matches="globalMatchHistory"
                                :is-completed="isCompleted"
                                :tournament-date="tournament.tournament_date"
                                @revertMatch="openRevertMatch"
                            />
                        </div>

                        <!-- Brackets Tab -->
                        <div v-if="activeTab === 'brackets' || fullscreenBracketId" class="animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <BracketView 
                                :brackets="brackets"
                                :active-bracket-id="activeBracketId"
                                :fullscreen-bracket-id="fullscreenBracketId"
                                :is-completed="isCompleted"
                                @selectBracket="selectBracket"
                                @clearSelection="clearSelection"
                                @toggleFullScreen="toggleFullScreen"
                                @chooseWinner="openConfirmWinner"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Winner Confirmation Dialog -->
        <Dialog :open="isConfirmWinnerOpen" @update:open="isConfirmWinnerOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Confirm Match Winner</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to declare this fighter as the winner? This will advance them to the next round.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4">
                    <div class="flex items-center justify-between p-4 bg-muted/50 rounded-lg border border-border">
                        <div class="font-medium">
                            <span v-if="confirmWinnerId === confirmWinnerMatch?.player_one_id" class="text-secondary font-bold">Blue: </span>
                            <span v-else class="text-primary font-bold">Green: </span>
                            {{ confirmWinnerId === confirmWinnerMatch?.player_one_id ? confirmWinnerMatch?.player_one : confirmWinnerMatch?.player_two }}
                        </div>
                        <Badge variant="outline" class="bg-accent text-accent-foreground border-accent/50">Winner</Badge>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="closeConfirmWinner">Cancel</Button>
                    <Button @click="confirmWinner" class="bg-primary hover:bg-primary/90 text-primary-foreground">Confirm Winner</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Revert Match Dialog -->
        <Dialog :open="isRevertMatchOpen" @update:open="isRevertMatchOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Revert Match Result</DialogTitle>
                    <DialogDescription>
                        This will reset the match status and remove the winner. If this match advanced a player to a subsequent match that has already started, you may need to revert that match first.
                    </DialogDescription>
                </DialogHeader>
                <div class="py-4" v-if="revertMatchItem">
                    <Alert variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertTitle>Warning</AlertTitle>
                        <AlertDescription>
                            This action cannot be undone if subsequent matches have been played.
                        </AlertDescription>
                    </Alert>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="closeRevertMatch">Cancel</Button>
                    <Button variant="destructive" @click="revertMatch">Revert Match</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>
