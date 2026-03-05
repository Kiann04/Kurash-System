<script setup lang="ts">
import { Check, Search, Users, Filter, Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import type { Player, TournamentWeightCategory, Registration } from '@/types/tournament'

interface Props {
    players: Player[]
    registrations: Registration[]
    selectedCategory: TournamentWeightCategory | null
    weightCategories: TournamentWeightCategory[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'update:registrations', value: Registration[]): void
}>()

// State for search query and gender filter
const searchQuery = ref('')
const genderFilter = ref<string>('all')

// Sync gender filter when category gender changes; if cleared, allow 'all'
watch(() => props.selectedCategory?.gender, (g) => {
    if (g) {
        const normalized = String(g).toLowerCase()
        genderFilter.value = normalized === 'male' || normalized === 'm' ? 'male' : 'female'
    } else {
        genderFilter.value = 'all'
    }
})

// Computed Property: Filters the list of players based on:
// 1. Player status (must not be inactive/expired)
// 2. Search query (matches name or club)
// 3. Gender filter (matches selected gender)
const filteredPlayers = computed(() => {
    const query = searchQuery.value.toLowerCase()

    return props.players.filter((player) => {
        // 1. Status Check: Active or Expiring Soon (Not Inactive/Expired)
        if (player.status.toLowerCase() === 'inactive') return false

        // Check expiry date if present
        if (player.membership_expires_at) {
            const expiryDate = new Date(player.membership_expires_at)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            // If expired, consider inactive
            if (expiryDate < today) return false
        }

        // 2. Search Check
        const matchesSearch =
            player.full_name.toLowerCase().includes(query) ||
            player.club.toLowerCase().includes(query)
        
        // 3. Gender logic:
        // If the selected category has a gender, enforce it; otherwise use the filter
        const playerGender = (player.gender || '').toLowerCase()
        let matchesGender = true
        const categoryGender = (props.selectedCategory?.gender || '').toLowerCase()
        if (categoryGender) {
            matchesGender =
                ((categoryGender === 'male' || categoryGender === 'm') && (playerGender === 'male' || playerGender === 'm')) ||
                ((categoryGender === 'female' || categoryGender === 'f') && (playerGender === 'female' || playerGender === 'f'))
        } else if (genderFilter.value !== 'all') {
            matchesGender =
                (genderFilter.value === 'male' && (playerGender === 'male' || playerGender === 'm')) ||
                (genderFilter.value === 'female' && (playerGender === 'female' || playerGender === 'f'))
        }

        return matchesSearch && matchesGender
    })
})

// Helper: Checks if a specific player is registered in the CURRENTLY selected category.
const isPlayerRegisteredInCurrentCategory = (playerId: number) => {
    return props.registrations.some(
        (reg) =>
            reg.player_id === playerId &&
            reg.tournament_weight_category_id === props.selectedCategory?.id
    )
}

// Helper: Eligibility for current category by gender
const isEligiblePlayer = (player: Player) => {
    if (!props.selectedCategory) return false
    const categoryGender = (props.selectedCategory.gender || '').toLowerCase()
    if (!categoryGender) return true
    const playerGender = (player.gender || '').toLowerCase()
    return (
        ((categoryGender === 'male' || categoryGender === 'm') && (playerGender === 'male' || playerGender === 'm')) ||
        ((categoryGender === 'female' || categoryGender === 'f') && (playerGender === 'female' || playerGender === 'f'))
    )
}

// Helper: Gender letter styling (M blue, F pink)
const getGenderClass = (gender: string) => {
    const g = (gender || '').toLowerCase()
    if (g === 'male' || g === 'm') {
        return 'text-blue-600 dark:text-blue-400'
    }
    return 'text-pink-600 dark:text-pink-400'
}

// Helper: Returns the ID of the weight category a player is registered in (if any).
const getPlayerRegistrationCategory = (playerId: number) => {
    const reg = props.registrations.find((r) => r.player_id === playerId)
    return reg ? reg.tournament_weight_category_id : null
}

// Action: Toggles a player's registration status.
// If registered in current category -> Removes registration.
// If not registered -> Adds registration to current category.
// Note: Logic allows checking if player is already in another category (commented out single-category rule).
const togglePlayerRegistration = (player: Player) => {
    if (!props.selectedCategory) return

    if (!isEligiblePlayer(player)) return

    const isRegistered = isPlayerRegisteredInCurrentCategory(player.id)
    let newRegistrations = [...props.registrations]

    if (isRegistered) {
        newRegistrations = newRegistrations.filter(
            (reg) => !(reg.player_id === player.id && reg.tournament_weight_category_id === props.selectedCategory?.id)
        )
    } else {
        // Remove from other categories first (if single category per player rule applies)
        // newRegistrations = newRegistrations.filter((reg) => reg.player_id !== player.id)
        
        // Add to current category
        newRegistrations.push({
            player_id: player.id,
            tournament_weight_category_id: props.selectedCategory.id,
        })
    }
    
    emit('update:registrations', newRegistrations)
}

// Helper: Generates 2-letter initials from a full name (e.g., "John Doe" -> "JD").
const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
}

// Helper: Calculates age based on Date of Birth string.
const calculateAge = (dobString?: string) => {
    if (!dobString) return '-'
    const dob = new Date(dobString)
    const diff = Date.now() - dob.getTime()
    const ageDate = new Date(diff)
    return Math.abs(ageDate.getUTCFullYear() - 1970)
}

// Helper: Determines the display text and style for the "Assignment" column.
// Returns 'Selected' (highlighted) if in current category, or the Category Name if in another.
const getPlayerAssignment = (playerId: number) => {
    if (isPlayerRegisteredInCurrentCategory(playerId)) {
        return { 
            text: props.selectedCategory?.name || 'Selected',
            class: 'text-indigo-600 font-medium' 
        }
    }
    const reg = props.registrations.find(r => r.player_id === playerId)
    if (!reg) return { text: '-', class: 'text-slate-500' }
    
    const category = props.weightCategories.find(c => c.id === reg.tournament_weight_category_id)
    return { 
        text: category ? `${category.name}` : 'Registered',
        class: 'text-slate-900 dark:text-slate-300'
    }
}
</script>

<template>
    <!-- Fixed height to prevent layout shifts when filtering -->
    <Card class="w-full h-150 border-slate-200 shadow-sm dark:bg-slate-950 dark:border-slate-800 flex flex-col">
        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 py-3">
            <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                    <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <Users class="w-4 h-4 text-indigo-500" />
                        Assign Players
                    </CardTitle>
                    <CardDescription class="text-xs">
                        Add eligible players to the selected category.
                    </CardDescription>
                </div>
                <Badge variant="outline" class="font-normal bg-white dark:bg-slate-900">
                    {{ filteredPlayers.length }} available
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0 flex-1 flex flex-col min-h-0">
            <!-- Filter Controls: Search + Gender -->
            <div class="p-3 border-b dark:border-slate-800 space-y-3 bg-white dark:bg-slate-950">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-slate-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search players..."
                            class="pl-9 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 h-9"
                        />
                    </div>
                    <div class="w-28">
                        <Select v-model="genderFilter">
                            <SelectTrigger class="h-9 dark:bg-slate-900 dark:border-slate-800" :disabled="!!props.selectedCategory && !!props.selectedCategory.gender">
                                <div class="flex items-center gap-2">
                                    <Filter class="w-3.5 h-3.5 text-slate-500" />
                                    <SelectValue placeholder="Gender" />
                                </div>
                            </SelectTrigger>
                            <SelectContent class="dark:bg-slate-900 dark:border-slate-800">
                                <SelectItem value="all">All</SelectItem>
                                <SelectItem value="male">Male</SelectItem>
                                <SelectItem value="female">Female</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <ScrollArea class="flex-1">
                <div class="relative w-full">
                    <table class="w-full caption-bottom text-sm table-fixed">
                        <TableHeader class="sticky top-0 bg-slate-50 dark:bg-slate-900 z-10 shadow-sm">
                            <TableRow class="hover:bg-transparent border-b border-slate-200 dark:border-slate-800">
                                <TableHead class="w-12"></TableHead>
                                <TableHead class="w-1/4">Name</TableHead>
                                <TableHead class="w-1/4">Club</TableHead>
                                <TableHead class="w-24">Gender</TableHead>
                                <TableHead class="w-16">Age</TableHead>
                                <TableHead class="w-32">Assignment</TableHead>
                                <TableHead class="w-24">Status</TableHead>
                                <TableHead class="w-20 text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody class="min-h-64">
                            <TableRow
                                v-for="player in filteredPlayers"
                                :key="player.id"
                                class="cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors border-b border-slate-100 dark:border-slate-800"
                                :class="{'bg-indigo-50/50 dark:bg-indigo-900/10': isPlayerRegisteredInCurrentCategory(player.id)}"
                                @click="togglePlayerRegistration(player)"
                            >
                                <TableCell>
                                    <div class="flex justify-center">
                                        <!-- Selection Indicator: Checkmark, Empty Circle, or Dot -->
                                        <div v-if="isPlayerRegisteredInCurrentCategory(player.id)" class="h-5 w-5 rounded-full bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                                            <Check class="h-3 w-3" />
                                        </div>
                                        <div v-else-if="!getPlayerRegistrationCategory(player.id)" class="h-5 w-5 rounded-full border-2 border-slate-300 dark:border-slate-700"></div>
                                        <div v-else class="h-5 w-5 flex items-center justify-center">
                                            <div class="h-2 w-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="font-medium text-slate-900 dark:text-slate-200">
                                    {{ player.full_name }}
                                </TableCell>
                                <TableCell class="text-slate-600 dark:text-slate-400">{{ player.club }}</TableCell>
                                <TableCell>
                                    <span :class="getGenderClass(player.gender)" class="font-medium">
                                        {{ (player.gender || '').charAt(0).toUpperCase() }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-slate-600 dark:text-slate-400">{{ calculateAge(player.dob) }}</TableCell>
                                <TableCell>
                                    <span :class="getPlayerAssignment(player.id).class" class="text-sm">
                                        {{ getPlayerAssignment(player.id).text }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="outline" class="bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800" v-if="player.status === 'active'">
                                        Active
                                    </Badge>
                                    <Badge variant="outline" class="bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700" v-else>
                                        {{ player.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                     <Button size="sm" variant="ghost" class="h-8 w-8 p-0 hover:bg-slate-200 dark:hover:bg-slate-800">
                                        <Plus v-if="isEligiblePlayer(player) && !isPlayerRegisteredInCurrentCategory(player.id) && !getPlayerRegistrationCategory(player.id)" class="h-4 w-4 text-slate-500 hover:text-indigo-600" />
                                        <Trash2 v-if="isPlayerRegisteredInCurrentCategory(player.id)" class="h-4 w-4 text-red-500" />
                                     </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredPlayers.length === 0">
                                <TableCell colspan="8" class="p-8 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Search class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                                        <p>No players found matching your criteria.</p>
                                        <Button 
                                            v-if="searchQuery || (!props.selectedCategory?.gender && genderFilter !== 'all')"
                                            variant="link" 
                                            class="mt-2 text-indigo-600 dark:text-indigo-400 h-auto p-0 text-xs"
                                            @click="searchQuery = ''; genderFilter = 'all'"
                                        >
                                            Clear filters
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </table>
                </div>
            </ScrollArea>
        </CardContent>
    </Card>
</template>
