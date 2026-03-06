<script setup lang="ts">
import { Check, Search, Users, Filter, Plus, Trash2, AlertTriangle } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
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

// State for alerts
const alertState = ref({
    isOpen: false,
    title: '',
    description: '',
    confirmAction: () => {},
})

// Gender filter is user-controlled and remains clickable regardless of selected category.

// Computed Property: Filters the list of players based on:
// 1. Search query (matches name or club)
// 2. Gender filter (matches selected gender)
const filteredPlayers = computed(() => {
    const query = searchQuery.value.toLowerCase()

    return props.players.filter((player) => {
        // 1. Search Check
        const matchesSearch =
            player.full_name.toLowerCase().includes(query) ||
            player.club.toLowerCase().includes(query)
        
        // 2. Gender logic:
        // Combine category requirement AND manual filter
        const playerGender = (player.gender || '').toLowerCase()
        
        // Check Manual Filter
        let matchesFilter = true
        if (genderFilter.value !== 'all') {
            matchesFilter =
                (genderFilter.value === 'male' && (playerGender === 'male' || playerGender === 'm')) ||
                (genderFilter.value === 'female' && (playerGender === 'female' || playerGender === 'f'))
        }

        return matchesSearch && matchesFilter
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
    // Eligibility logic is now handled in togglePlayerRegistration with a warning
    return true
}

// Helper: Gender letter styling (M blue, F green)
const getGenderClass = (gender: string) => {
    const g = (gender || '').toLowerCase()
    if (g === 'male' || g === 'm') {
        return 'text-secondary'
    }
    return 'text-primary'
}

// Helper: Returns the ID of the weight category a player is registered in (if any).
const getPlayerRegistrationCategory = (playerId: number) => {
    const reg = props.registrations.find((r) => r.player_id === playerId)
    return reg ? reg.tournament_weight_category_id : null
}

// Helper: Check if player's membership is expired
const isMembershipExpired = (player: Player) => {
    if (player.status.toLowerCase() === 'inactive') return true
    if (player.membership_expires_at) {
        const expiryDate = new Date(player.membership_expires_at)
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        return expiryDate < today
    }
    return false
}

// Helper: Get days left before expiration
const getDaysLeft = (player: Player) => {
    if (!player.membership_expires_at) return 0
    const expiryDate = new Date(player.membership_expires_at)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const diffTime = expiryDate.getTime() - today.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays
}

// Action: Toggles a player's registration status.
const togglePlayerRegistration = (player: Player) => {
    if (!props.selectedCategory) return

    // If registered, remove registration (always allowed)
    if (isPlayerRegisteredInCurrentCategory(player.id)) {
        performToggle(player)
        return
    }

    // 1. Inactive/Expired Block
    if (isMembershipExpired(player)) {
        toast.error('Cannot add inactive or expired player to the tournament.')
        return
    }

    // 2. Gender Mismatch Check
    const categoryGender = (props.selectedCategory.gender || '').toLowerCase()
    const playerGender = (player.gender || '').toLowerCase()
    let isGenderMismatch = false
    if (categoryGender) {
        const isMatch = ((categoryGender === 'male' || categoryGender === 'm') && (playerGender === 'male' || playerGender === 'm')) ||
                        ((categoryGender === 'female' || categoryGender === 'f') && (playerGender === 'female' || playerGender === 'f'))
        if (!isMatch) isGenderMismatch = true
    }

    if (isGenderMismatch) {
        alertState.value = {
            isOpen: true,
            title: 'Gender Mismatch Warning',
            description: `You are assigning a ${player.gender} player to a ${props.selectedCategory.gender} category. Do you want to proceed?`,
            confirmAction: () => {
                alertState.value.isOpen = false
                // Proceed to next check (Expiring)
                checkExpiringAndProceed(player)
            }
        }
        return
    }

    // 3. Expiring Check
    checkExpiringAndProceed(player)
}

const checkExpiringAndProceed = (player: Player) => {
    // Check if expiring (e.g., within 30 days)
    // We check date calculation since status from backend might be 'active'
    const days = getDaysLeft(player)
    const isExpiringSoon = days > 0 && days <= 30
    
    if (player.status.toLowerCase() === 'expiring' || isExpiringSoon) {
        alertState.value = {
            isOpen: true,
            title: 'Expiring Membership Warning',
            description: `The player's membership is expiring in ${days} days. Do you want to proceed?`,
            confirmAction: () => {
                alertState.value.isOpen = false
                performToggle(player)
            }
        }
        return
    }
    
    performToggle(player)
}

const performToggle = (player: Player) => {
    const isRegistered = isPlayerRegisteredInCurrentCategory(player.id)
    let newRegistrations = [...props.registrations]

    if (isRegistered) {
        newRegistrations = newRegistrations.filter(
            (reg) => !(reg.player_id === player.id && reg.tournament_weight_category_id === props.selectedCategory?.id)
        )
    } else {
        newRegistrations.push({
            player_id: player.id,
            tournament_weight_category_id: props.selectedCategory!.id,
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
            class: 'text-primary font-medium' 
        }
    }
    const reg = props.registrations.find(r => r.player_id === playerId)
    if (!reg) return { text: '-', class: 'text-muted-foreground' }
    
    const category = props.weightCategories.find(c => c.id === reg.tournament_weight_category_id)
    return { 
        text: category ? `${category.name}` : 'Registered',
        class: 'text-foreground'
    }
}
</script>

<template>
    <!-- Fixed height to prevent layout shifts when filtering -->
    <Card class="w-full h-150 border-border shadow-sm bg-card text-card-foreground flex flex-col">
        <CardHeader class="border-b bg-muted/50 py-3">
            <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                    <CardTitle class="text-base font-semibold text-foreground flex items-center gap-2">
                        <Users class="w-4 h-4 text-primary" />
                        Assign Players
                    </CardTitle>
                    <CardDescription class="text-xs text-muted-foreground">
                        Add eligible players to the selected category.
                    </CardDescription>
                </div>
                <Badge variant="outline" class="font-normal bg-background">
                    {{ filteredPlayers.length }} available
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0 flex-1 flex flex-col min-h-0">
            <!-- Filter Controls: Search + Gender -->
            <div class="p-3 border-b border-border space-y-3 bg-background">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search players..."
                            class="pl-9 bg-background border-input h-9"
                        />
                    </div>
                    <div class="w-28">
                        <Select v-model="genderFilter">
                            <SelectTrigger class="h-9 bg-background border-input">
                                <div class="flex items-center gap-2">
                                    <Filter class="w-3.5 h-3.5 text-muted-foreground" />
                                    <SelectValue placeholder="Gender" />
                                </div>
                            </SelectTrigger>
                            <SelectContent>
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
                        <TableHeader class="sticky top-0 bg-muted/50 z-10 shadow-sm">
                            <TableRow class="hover:bg-transparent border-b border-border">
                                <TableHead class="w-12"></TableHead>
                                <TableHead class="w-1/4 text-muted-foreground">Name</TableHead>
                                <TableHead class="w-1/4 text-muted-foreground">Club</TableHead>
                                <TableHead class="w-24 text-muted-foreground">Gender</TableHead>
                                <TableHead class="w-16 text-muted-foreground">Age</TableHead>
                                <TableHead class="w-32 text-muted-foreground">Assignment</TableHead>
                                <TableHead class="w-24 text-muted-foreground">Status</TableHead>
                                <TableHead class="w-20 text-right text-muted-foreground">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody class="min-h-64">
                            <TableRow
                                v-for="player in filteredPlayers"
                                :key="player.id"
                                class="cursor-pointer hover:bg-muted/50 transition-colors border-b border-border"
                                :class="{'bg-primary/5': isPlayerRegisteredInCurrentCategory(player.id)}"
                                @click="togglePlayerRegistration(player)"
                            >
                                <TableCell>
                                    <div class="flex justify-center">
                                        <!-- Selection Indicator: Checkmark, Empty Circle, or Dot -->
                                        <div v-if="isPlayerRegisteredInCurrentCategory(player.id)" class="h-5 w-5 rounded-full bg-primary flex items-center justify-center text-primary-foreground shadow-sm">
                                            <Check class="h-3 w-3" />
                                        </div>
                                        <div v-else-if="!getPlayerRegistrationCategory(player.id)" class="h-5 w-5 rounded-full border-2 border-muted-foreground/30"></div>
                                        <div v-else class="h-5 w-5 flex items-center justify-center">
                                            <div class="h-2 w-2 rounded-full bg-muted-foreground/50"></div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="font-medium text-foreground">
                                    {{ player.full_name }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ player.club }}</TableCell>
                                <TableCell>
                                    <span :class="getGenderClass(player.gender)" class="font-medium">
                                        {{ (player.gender || '').charAt(0).toUpperCase() }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-muted-foreground">{{ calculateAge(player.dob) }}</TableCell>
                                <TableCell>
                                    <span :class="getPlayerAssignment(player.id).class" class="text-sm">
                                        {{ getPlayerAssignment(player.id).text }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <Badge 
                                        variant="outline" 
                                        class="font-medium"
                                        :class="{
                                            'bg-primary/10 text-primary border-primary/20': player.status === 'active',
                                            'bg-accent/15 text-accent-foreground border-accent/20': player.status === 'expiring',
                                            'bg-muted text-muted-foreground border-border': player.status !== 'active' && player.status !== 'expiring'
                                        }"
                                    >
                                        {{ player.status.charAt(0).toUpperCase() + player.status.slice(1) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                     <Button size="sm" variant="ghost" class="h-8 w-8 p-0 hover:bg-muted">
                                        <Plus v-if="isEligiblePlayer(player) && !isPlayerRegisteredInCurrentCategory(player.id) && !getPlayerRegistrationCategory(player.id)" class="h-4 w-4 text-muted-foreground hover:text-primary" />
                                        <Trash2 v-if="isPlayerRegisteredInCurrentCategory(player.id)" class="h-4 w-4 text-destructive" />
                                     </Button>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="filteredPlayers.length === 0">
                                <TableCell colspan="8" class="p-8 text-center text-muted-foreground">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Search class="h-8 w-8 text-muted-foreground/50" />
                                        <p>No players found matching your criteria.</p>
                                        <Button 
                                            v-if="searchQuery || (!props.selectedCategory?.gender && genderFilter !== 'all')"
                                            variant="link" 
                                            class="mt-2 text-primary h-auto p-0 text-xs"
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
    <AlertDialog :open="alertState.isOpen" @update:open="alertState.isOpen = $event">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5 text-accent" />
                    {{ alertState.title }}
                </AlertDialogTitle>
                <AlertDialogDescription class="text-muted-foreground">
                    {{ alertState.description }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="alertState.isOpen = false">
                    Cancel
                </AlertDialogCancel>
                <AlertDialogAction @click="alertState.confirmAction" class="bg-primary hover:bg-primary/90 text-primary-foreground">
                    Proceed
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
