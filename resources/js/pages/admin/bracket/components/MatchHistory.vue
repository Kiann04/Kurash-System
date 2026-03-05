<script setup lang="ts">
/**
 * MatchHistory Component
 * 
 * Displays a chronological list of completed matches.
 * Allows administrators to review results and revert matches if necessary.
 */
import { Trophy } from 'lucide-vue-next'
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
import type { MatchItem, BracketItem } from '@/types/bracket'

/**
 * Component props
 * @property matches - List of completed matches with bracket details
 * @property isCompleted - Whether the tournament is completed (disables actions)
 */
const props = defineProps<{
    matches: Array<MatchItem & { bracket: BracketItem }>
    isCompleted: boolean
    tournamentDate?: string
}>()

/**
 * Component events
 */
const emit = defineEmits<{
    (e: 'revertMatch', match: MatchItem): void
}>()

/**
 * Emits an event to revert a completed match.
 * @param match The match to revert
 */
const revertMatch = (match: MatchItem) => {
    if (props.isCompleted) return
    emit('revertMatch', match)
}
</script>

<template>
    <!-- 
        Match History Display
        Shows a scrollable table of all completed matches.
        Allows reverting match results if needed (and authorized).
    -->
    <Card class="overflow-hidden shadow-sm dark:bg-slate-950 dark:border-slate-800">
        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 py-4">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <CardTitle class="text-base font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">
                        Match History
                    </CardTitle>
                    <CardDescription>
                        Recently completed matches
                        <span v-if="tournamentDate" class="ml-2 text-xs text-muted-foreground">
                            • {{ new Date(tournamentDate).toLocaleDateString() }}
                        </span>
                    </CardDescription>
                </div>
                <Badge variant="secondary" class="bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 shadow-sm">{{ matches.length }} Completed</Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div class="max-h-150 overflow-auto">
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
                            v-for="m in matches" 
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
                                    class="h-8 text-xs transition-all duration-200 border-slate-300 dark:border-slate-700 hover:bg-red-50 hover:text-red-600 hover:border-red-300 dark:hover:bg-red-900/20 dark:hover:text-red-400 dark:hover:border-red-500 hover:shadow-sm rounded-md"
                                    :disabled="isCompleted"
                                    @click="revertMatch(m)"
                                >
                                    Revert
                                </Button>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="matches.length === 0">
                            <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                No completed matches.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
