<script setup lang="ts">
/**
 * MatchList Component
 * 
 * Displays a comprehensive list of all matches across all brackets.
 * Used for managing the schedule and entering results.
 */
import { User, Check } from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
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
import { useBracketLogic } from '@/composables/useBracketLogic'
import { useInitials } from '@/composables/useInitials'
import type { MatchItem, BracketItem } from '@/types/bracket'

/**
 * Component props
 * @property matches - List of all scheduled matches with their associated bracket details
 * @property isCompleted - Whether the tournament is marked as completed
 */
const props = defineProps<{
    matches: Array<MatchItem & { bracket: BracketItem }>
    isCompleted: boolean
}>()

/**
 * Component events
 */
const emit = defineEmits<{
    (e: 'confirmWinner', match: MatchItem, winnerId: number): void
}>()

/**
 * Helper functions from composables
 */
const { roundLabel, finalRoundNumber } = useBracketLogic()
const { getInitials } = useInitials()

/**
 * Emits an event to confirm the winner of a match.
 * Performs validation to ensure a winner is selected and the tournament is active.
 * 
 * @param match The match object
 * @param winnerId The ID of the selected winner
 */
const confirmAndChooseWinner = (match: MatchItem, winnerId: number | null) => {
    if (props.isCompleted) return
    if (!winnerId) return
    
    emit('confirmWinner', match, winnerId)
}
</script>

<template>
    <!-- 
        Match List Display
        Shows a scrollable table of all scheduled matches.
        Allows confirming winners for ongoing matches.
    -->
    <Card class="overflow-hidden shadow-sm bg-card border-border">
        <CardHeader class="border-b bg-muted/50 py-4">
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <CardTitle class="text-base font-semibold uppercase tracking-wider text-foreground">
                        Match List (All Brackets)
                    </CardTitle>
                    <CardDescription>Manage ongoing and upcoming matches</CardDescription>
                </div>
                <Badge variant="secondary" class="bg-background border-border shadow-sm">{{ matches.length }} Scheduled</Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0">
            <div class="max-h-150 overflow-auto">
                <Table>
                    <TableHeader class="bg-muted/50 sticky top-0 z-10">
                        <TableRow class="hover:bg-transparent border-b border-border">
                            <TableHead class="w-16 font-semibold text-muted-foreground">ID</TableHead>
                            <TableHead class="font-semibold text-muted-foreground">Category</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Round</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Match</TableHead>
                            <TableHead class="font-semibold text-muted-foreground w-1/4">Player One</TableHead>
                            <TableHead class="font-semibold text-muted-foreground w-1/4">Player Two</TableHead>
                            <TableHead class="text-center font-semibold text-muted-foreground">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow 
                            v-for="m in matches" 
                            :key="m.id"
                            class="hover:bg-muted/50 transition-colors border-b border-border"
                        >
                            <TableCell class="text-muted-foreground font-mono text-xs">{{ m.id }}</TableCell>
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
                                    {{ roundLabel(finalRoundNumber(m.bracket), m.round_number, m.bracket.entrant_count, m.bracket.format) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-3">
                                    <template v-if="m.player_one">
                                        <Avatar class="h-8 w-8 border border-border">
                                            <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_one}&background=random`" />
                                            <AvatarFallback>{{ getInitials(m.player_one) }}</AvatarFallback>
                                        </Avatar>
                                        <span class="text-sm text-foreground">{{ m.player_one }}</span>
                                    </template>
                                    <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                </div>
                            </TableCell>
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-3">
                                    <template v-if="m.player_two">
                                        <Avatar class="h-8 w-8 border border-border">
                                            <AvatarImage :src="`https://ui-avatars.com/api/?name=${m.player_two}&background=random`" />
                                            <AvatarFallback>{{ getInitials(m.player_two) }}</AvatarFallback>
                                        </Avatar>
                                        <span class="text-sm text-foreground">{{ m.player_two }}</span>
                                    </template>
                                    <span v-else class="text-muted-foreground italic text-sm">BYE</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Button
                                         size="sm"
                                         variant="secondary"
                                         class="w-full sm:w-auto h-8 text-xs"
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
                                        variant="default"
                                        class="w-full sm:w-auto h-8 text-xs"
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
                        <TableRow v-if="matches.length === 0">
                            <TableCell colspan="7" class="h-24 text-center text-muted-foreground">
                                No scheduled matches.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </CardContent>
    </Card>
</template>
