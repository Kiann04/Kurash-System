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
    <Card class="overflow-hidden shadow-sm bg-card border-border">
        <CardHeader class="border-b bg-muted/50 py-4">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <CardTitle class="text-base font-semibold uppercase tracking-wider text-foreground">
                        Match History
                    </CardTitle>
                    <CardDescription>
                        Recently completed matches
                        <span v-if="tournamentDate" class="ml-2 text-xs text-muted-foreground">
                            • {{ new Date(tournamentDate).toLocaleDateString() }}
                        </span>
                    </CardDescription>
                </div>
                <Badge variant="secondary" class="bg-background border-border shadow-sm">{{ matches.length }} Completed</Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div class="max-h-150 overflow-auto">
                <Table>
                    <TableHeader class="bg-muted/50 sticky top-0 z-10 backdrop-blur-sm">
                        <TableRow class="hover:bg-transparent border-b border-border">
                            <TableHead class="font-semibold text-muted-foreground">Category</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Round</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Match</TableHead>
                            <TableHead class="font-semibold text-muted-foreground">Winner</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow 
                            v-for="m in matches" 
                            :key="`hist-${m.id}`"
                            class="hover:bg-muted/50 transition-colors border-b border-border"
                        >
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium text-foreground">{{ m.bracket.gender }}</span>
                                    <span class="text-xs text-muted-foreground">{{ m.bracket.age_category }} · {{ m.bracket.weight_category }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="outline" class="bg-muted text-muted-foreground border-border font-mono text-xs">
                                    {{ m.round_number }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary" class="font-normal text-xs bg-primary/10 text-primary hover:bg-primary/20 border-transparent">
                                    M{{ m.match_number }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Trophy class="h-4 w-4 text-accent fill-accent" />
                                    <span class="font-medium text-foreground">{{ m.winner || '—' }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-8 text-xs transition-all duration-200 border-input hover:bg-destructive/10 hover:text-destructive hover:border-destructive/50 hover:shadow-sm rounded-md"
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
