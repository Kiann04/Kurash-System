<script setup lang="ts">
import { computed, ref } from 'vue'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Button } from '@/components/ui/button'
import { Trash2, ChevronDown, ChevronRight } from 'lucide-vue-next'
import { Player, TournamentWeightCategory, Registration } from '@/types/tournament'

interface Props {
    registrations: Registration[]
    players: Player[]
    weightCategories: TournamentWeightCategory[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'remove', playerId: number, categoryId: number): void
}>()

// State for collapsed categories (stores category IDs)
const collapsedCategories = ref<Set<number>>(new Set())

// Action: Toggles the collapsed state of a category group
const toggleCategory = (categoryId: number) => {
    const newSet = new Set(collapsedCategories.value)
    if (newSet.has(categoryId)) {
        newSet.delete(categoryId)
    } else {
        newSet.add(categoryId)
    }
    collapsedCategories.value = newSet
}

// Helper: Retrieves the full name of a player by their ID
const getPlayerName = (playerId: number) =>
    props.players.find((p) => p.id === playerId)?.full_name ?? 'Unknown Player'

// Helper: Retrieves details of a weight category by its ID
const getCategoryDetails = (categoryId: number) => {
    const category = props.weightCategories.find((c) => c.id === categoryId)
    return category ? {
        name: category.name,
        gender: category.gender,
        ageCategory: category.age_category_name
    } : null
}

// Computed Property: Groups registrations by weight category ID
const groupedRegistrations = computed(() => {
    const groups: Record<number, Registration[]> = {}
    props.registrations.forEach((reg) => {
        if (!groups[reg.tournament_weight_category_id]) {
            groups[reg.tournament_weight_category_id] = []
        }
        groups[reg.tournament_weight_category_id].push(reg)
    })
    return groups
})

// Computed Property: Returns category IDs sorted by player count (descending) then category name (ascending)
const sortedCategoryIds = computed(() => {
    return Object.keys(groupedRegistrations.value)
        .map(Number)
        .sort((a, b) => {
            // Sort by count (descending)
            const countA = groupedRegistrations.value[a].length
            const countB = groupedRegistrations.value[b].length
            if (countA !== countB) return countB - countA

            // Then by name (ascending)
            const catA = props.weightCategories.find(c => c.id === a)
            const catB = props.weightCategories.find(c => c.id === b)
            return (catA?.name || '').localeCompare(catB?.name || '', undefined, { numeric: true })
        })
})
</script>

<template>
    <Card class="shadow-sm border-slate-200 dark:bg-slate-950 dark:border-slate-800">
        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 pb-4">
            <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">Registration Summary</CardTitle>
            <CardDescription>Review all registered players by category.</CardDescription>
        </CardHeader>
        <CardContent class="p-0">
            <!-- Empty State -->
            <div v-if="registrations.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400">
                <p class="text-sm">No players registered yet.</p>
            </div>
            
            <!-- Grouped Registrations List -->
            <ScrollArea v-else class="h-88">
                <div class="divide-y dark:divide-slate-800">
                    <div v-for="categoryId in sortedCategoryIds" :key="categoryId" class="p-4">
                        <!-- Category Header (Clickable to toggle collapse) -->
                        <div 
                            class="flex items-center justify-between cursor-pointer select-none group"
                            @click="toggleCategory(categoryId)"
                        >
                            <h3 class="font-medium text-sm text-indigo-600 mb-0 dark:text-indigo-400 flex items-center gap-2">
                                <component 
                                    :is="collapsedCategories.has(categoryId) ? ChevronRight : ChevronDown" 
                                    class="h-4 w-4 text-slate-400 group-hover:text-indigo-500 transition-colors"
                                />
                                <span class="capitalize font-bold">{{ getCategoryDetails(categoryId)?.gender }}</span>
                                <span class="text-slate-400">•</span>
                                <span>{{ getCategoryDetails(categoryId)?.ageCategory }}</span>
                                <span class="text-slate-400">•</span>
                                <span>{{ getCategoryDetails(categoryId)?.name }}</span>
                            </h3>
                            <span class="text-xs text-slate-500 dark:text-slate-500 font-normal">
                                ({{ groupedRegistrations[categoryId].length }} players)
                            </span>
                        </div>
                        
                        <!-- Players List (Collapsible) -->
                        <div 
                            v-show="!collapsedCategories.has(categoryId)"
                            class="mt-2 rounded-md border border-slate-200 dark:border-slate-800 overflow-hidden"
                        >
                            <div
                                v-for="reg in groupedRegistrations[categoryId]"
                                :key="reg.player_id"
                                class="flex items-center justify-between px-3 py-2 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-6 w-6 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300 flex items-center justify-center text-xs font-bold">
                                        {{ getPlayerName(reg.player_id).charAt(0) }}
                                    </div>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                        {{ getPlayerName(reg.player_id) }}
                                    </span>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-7 w-7 text-slate-400 hover:text-destructive hover:bg-destructive/10 dark:text-slate-500 dark:hover:text-red-400"
                                    @click="emit('remove', reg.player_id, categoryId)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </CardContent>
    </Card>
</template>
