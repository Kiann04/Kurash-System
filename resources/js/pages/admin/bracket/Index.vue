<script setup lang="ts">
/**
 * Bracket Generation Index Page
 * 
 * Displays lists of tournaments with pending and generated brackets.
 * Allows administrators to generate new brackets or regenerate existing ones.
 */
import { Head, Link, router } from '@inertiajs/vue3'
import { 
    Trophy, 
    Calendar, 
    Users, 
    Play, 
    Eye,
    ListTree,
    ArrowRight,
    CheckCircle2,
    Clock,
    RefreshCw,
    Trash2,
    MapPin
} from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { route } from 'ziggy-js'
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
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'

/**
 * Interface representing a tournament in the context of bracket generation
 */
interface Tournament {
    id: number
    name: string
    location: string | null
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count: number
}

/**
 * Props received from the Inertia controller
 * @property generated Tournaments that already have brackets
 * @property not_generated Tournaments pending bracket generation
 */
const props = defineProps<{
    generated: Tournament[]
    not_generated: Tournament[]
}>()

// State for search filter and regeneration modal
const search = ref('')
const tournamentToRegenerate = ref<Tournament | null>(null)
const isRegenerateDialogOpen = ref(false)

/**
 * Triggers bracket generation for a specific tournament.
 * This will create matches based on registered players and weight categories.
 * 
 * @param tournamentId The ID of the tournament
 */
const generate = (tournamentId: number) => {
    router.post(route('admin.tournaments.brackets.generate', tournamentId))
}

/**
 * Opens the regeneration confirmation dialog.
 * 
 * @param tournament The tournament object to regenerate
 */
const confirmRegenerate = (tournament: Tournament) => {
    tournamentToRegenerate.value = tournament
    isRegenerateDialogOpen.value = true
}

/**
 * Executes the regeneration process after confirmation.
 * This will wipe existing matches and create new ones.
 */
const handleRegenerate = () => {
    if (tournamentToRegenerate.value) {
        generate(tournamentToRegenerate.value.id)
        isRegenerateDialogOpen.value = false
        tournamentToRegenerate.value = null
    }
}

/**
 * Formats a date string into a localized human-readable format.
 */
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

/**
 * Returns the appropriate CSS classes for a tournament status badge.
 */
const getStatusClass = (status: string) => {
    switch (status) {
        case 'draft':
            return 'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20'
        case 'open':
            return 'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20'
        case 'ongoing':
            return 'bg-secondary/15 text-secondary hover:bg-secondary/25 border-secondary/20'
        case 'completed':
            return 'bg-primary/15 text-primary hover:bg-primary/25 border-primary/20'
        default:
            return 'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20'
    }
}

/**
 * Filters the list of tournaments without brackets based on search query.
 */
const filteredNotGenerated = computed(() => {
    if (!search.value) return props.not_generated
    return props.not_generated.filter(t => 
        t.name.toLowerCase().includes(search.value.toLowerCase())
    )
})

/**
 * Filters the list of tournaments with generated brackets based on search query.
 */
const filteredGenerated = computed(() => {
    if (!search.value) return props.generated
    return props.generated.filter(t => 
        t.name.toLowerCase().includes(search.value.toLowerCase())
    )
})
</script>

<template>
    <Head title="Generate Brackets" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Generate Brackets</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Manage and generate brackets for your tournaments.
                    </p>
                </div>
                <div class="w-full md:w-72">
                    <Input 
                        v-model="search" 
                        placeholder="Search tournaments..." 
                        class="bg-background border-input"
                    />
                </div>
            </div>

            <div class="grid gap-6">
                <!-- Pending Generation -->
                <Card class="border-l-4 border-l-secondary shadow-sm bg-card text-card-foreground">
                    <CardHeader class="border-b bg-muted/50">
                        <CardTitle class="flex items-center gap-2 text-lg text-foreground">
                            <ListTree class="h-5 w-5 text-secondary" />
                            Pending Generation
                        </CardTitle>
                        <CardDescription class="text-muted-foreground">
                            Tournaments that need brackets generated.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="relative w-full overflow-auto rounded-md border border-border">
                            <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50 hover:bg-muted/50">
                                    <TableHead class="w-[30%] font-semibold text-muted-foreground">Tournament</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-muted-foreground">Date</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-muted-foreground">Location</TableHead>
                                    <TableHead class="font-semibold text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-center font-semibold text-muted-foreground">Registrations</TableHead>
                                    <TableHead class="text-right font-semibold text-muted-foreground">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="tournament in filteredNotGenerated" 
                                    :key="tournament.id"
                                    class="hover:bg-muted/50 transition-colors border-b border-border"
                                >
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-foreground">{{ tournament.name }}</span>
                                            <span class="text-xs text-muted-foreground md:hidden flex items-center gap-1">
                                                <Calendar class="h-3 w-3 text-white" />
                                                {{ formatDate(tournament.tournament_date) }}
                                            </span>
                                            <span v-if="tournament.location" class="text-xs text-muted-foreground md:hidden flex items-center gap-1 mt-0.5">
                                                <MapPin class="h-3 w-3 text-white" />
                                                {{ tournament.location }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 text-white" />
                                            {{ formatDate(tournament.tournament_date) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <MapPin class="h-3.5 w-3.5 text-white" />
                                            {{ tournament.location || '-' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="['capitalize shadow-none font-normal w-20 justify-center border-transparent', getStatusClass(tournament.status)]">
                                            {{ tournament.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Users class="h-3.5 w-3.5 text-white" />
                                            <span class="text-white font-normal">{{ tournament.registrations_count }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                size="sm" 
                                                :disabled="tournament.registrations_count < 2"
                                                @click="generate(tournament.id)"
                                                class="bg-secondary hover:bg-secondary/90 text-secondary-foreground shadow-sm transition-all"
                                            >
                                                <Play class="h-3.5 w-3.5 mr-1.5 fill-current" />
                                                Generate
                                            </Button>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                                class="hover:bg-muted text-muted-foreground hover:text-foreground"
                                            >
                                                <Link :href="route('admin.tournaments.brackets.show', tournament.id)">
                                                    View
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredNotGenerated.length === 0">
                                    <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                        No pending tournaments found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Generated Brackets -->
                <Card class="shadow-sm bg-card text-card-foreground border-border">
                    <CardHeader class="border-b bg-muted/50">
                        <CardTitle class="flex items-center gap-2 text-lg text-foreground">
                            <CheckCircle2 class="h-5 w-5 text-primary" />
                            Generated Brackets
                        </CardTitle>
                        <CardDescription class="text-muted-foreground">
                            Tournaments with active brackets.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="relative w-full overflow-auto rounded-md border border-border">
                            <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50 hover:bg-muted/50">
                                    <TableHead class="w-[30%] font-semibold text-muted-foreground">Tournament</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-muted-foreground">Date</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-muted-foreground">Location</TableHead>
                                    <TableHead class="font-semibold text-muted-foreground">Status</TableHead>
                                    <TableHead class="text-center font-semibold text-muted-foreground">Registrations</TableHead>
                                    <TableHead class="text-right font-semibold text-muted-foreground">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="tournament in filteredGenerated" 
                                    :key="tournament.id"
                                    class="hover:bg-muted/50 transition-colors border-b border-border"
                                >
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-foreground">{{ tournament.name }}</span>
                                            <span class="text-xs text-muted-foreground md:hidden flex items-center gap-1">
                                                <Calendar class="h-3 w-3 text-white" />
                                                {{ formatDate(tournament.tournament_date) }}
                                            </span>
                                            <span v-if="tournament.location" class="text-xs text-muted-foreground md:hidden flex items-center gap-1 mt-0.5">
                                                <MapPin class="h-3 w-3 text-white" />
                                                {{ tournament.location }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 text-white" />
                                            {{ formatDate(tournament.tournament_date) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <MapPin class="h-3.5 w-3.5 text-white" />
                                            {{ tournament.location || '-' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="['capitalize shadow-none font-normal w-20 justify-center border-transparent', getStatusClass(tournament.status)]">
                                            {{ tournament.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Users class="h-3.5 w-3.5 text-white" />
                                            <span class="text-white font-normal">{{ tournament.registrations_count }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                size="sm" 
                                                :disabled="tournament.registrations_count < 2"
                                                @click="confirmRegenerate(tournament)"
                                                class="bg-primary hover:bg-primary/90 text-primary-foreground shadow-sm transition-all"
                                                title="Regenerate Brackets"
                                            >
                                                <RefreshCw class="h-3.5 w-3.5 mr-1.5" />
                                                Regenerate
                                            </Button>
                                            <Button 
                                                variant="secondary" 
                                                size="sm" 
                                                as-child
                                                class="shadow-sm transition-all"
                                            >
                                                <Link :href="route('admin.tournaments.brackets.show', tournament.id)">
                                                    <Eye class="h-3.5 w-3.5 mr-1.5" />
                                                    View Bracket
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredGenerated.length === 0">
                                    <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                        No generated brackets found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Regenerate Dialog -->
        <Dialog v-model:open="isRegenerateDialogOpen">
            <DialogContent class="sm:max-w-md bg-card border-border">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-foreground">
                        <RefreshCw class="h-5 w-5 text-accent" />
                        Regenerate Brackets
                    </DialogTitle>
                    <DialogDescription class="text-muted-foreground">
                        Are you sure you want to regenerate the brackets for <strong>{{ tournamentToRegenerate?.name }}</strong>?
                        This will delete all current matches and scores. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button 
                        variant="outline" 
                        @click="isRegenerateDialogOpen = false"
                        class="border-input hover:bg-accent hover:text-accent-foreground"
                    >
                        Cancel
                    </Button>
                    <Button 
                        variant="destructive" 
                        @click="handleRegenerate"
                    >
                        Regenerate
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
