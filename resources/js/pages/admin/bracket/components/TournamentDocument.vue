<script setup lang="ts">
/**
 * TournamentDocument Component
 * 
 * Displays a printable list of all matches in the tournament.
 * Serves as the official record or schedule document.
 */
import { Download } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { MatchItem, BracketItem, TournamentItem } from '@/types/bracket'

/**
 * Component props
 * @property tournament - The tournament details
 * @property matches - List of all matches to include in the document
 */
const props = defineProps<{
    tournament: TournamentItem
    matches: Array<MatchItem & { bracket: BracketItem }>
}>()

/**
 * Triggers the browser's print dialog to export the document as PDF.
 */
const exportPdf = () => {
    window.print()
}
</script>

<template>
    <!-- 
        Tournament Document Card
        Designed to be printable. Contains logic to hide/show elements based on print media query.
        The table is scrollable on screen but expands fully when printed.
    -->
    <Card class="print:border-none print:shadow-none overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
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
            <div class="hidden print:block mb-4 p-4">
                <h1 class="text-lg font-bold">{{ props.tournament.name }}</h1>
                <div class="text-xs text-slate-600 dark:text-slate-400">
                    {{ props.tournament.tournament_date }} - {{ props.tournament.status }}
                </div>
            </div>
            <div class="max-h-150 overflow-auto print:max-h-none print:overflow-visible">
                <Table>
                    <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm print:static">
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
                            v-for="(m, idx) in matches" 
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
                                    R{{ m.round_number }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary" class="font-mono text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    #{{ m.match_number }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span :class="{'font-bold text-green-600 dark:text-green-400': m.winner_id === m.player_one_id}">
                                    {{ m.player_one || 'BYE' }}
                                </span>
                            </TableCell>
                            <TableCell>
                                <span :class="{'font-bold text-green-600 dark:text-green-400': m.winner_id === m.player_two_id}">
                                    {{ m.player_two || 'BYE' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge :variant="m.status === 'completed' ? 'default' : 'outline'" class="capitalize text-xs">
                                    {{ m.status }}
                                </Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="matches.length === 0">
                            <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                No matches scheduled.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
