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
        <div class="p-6 space-y-6 dark:bg-slate-950">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight dark:text-slate-100">Generate Brackets</h1>
                    <p class="text-sm text-muted-foreground mt-1 dark:text-slate-400">
                        Manage and generate brackets for your tournaments.
                    </p>
                </div>
                <div class="w-full md:w-72">
                    <Input 
                        v-model="search" 
                        placeholder="Search tournaments..." 
                        class="bg-background dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
                    />
                </div>
            </div>

            <div class="grid gap-6">
                <!-- Pending Generation -->
                <Card class="border-l-4 border-l-blue-500 shadow-sm dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800">
                        <CardTitle class="flex items-center gap-2 text-lg dark:text-slate-100">
                            <ListTree class="h-5 w-5 text-blue-500" />
                            Pending Generation
                        </CardTitle>
                        <CardDescription class="dark:text-slate-400">
                            Tournaments that need brackets generated.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10">
                                <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                    <TableHead class="w-[30%] font-semibold text-slate-500 dark:text-slate-400">Tournament</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-slate-500 dark:text-slate-400">Date</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-slate-500 dark:text-slate-400">Location</TableHead>
                                    <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Status</TableHead>
                                    <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Registrations</TableHead>
                                    <TableHead class="text-right font-semibold text-slate-500 dark:text-slate-400">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="tournament in filteredNotGenerated" 
                                    :key="tournament.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800"
                                >
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900 dark:text-slate-200">{{ tournament.name }}</span>
                                            <span class="text-xs text-muted-foreground md:hidden dark:text-slate-500 flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(tournament.tournament_date) }}
                                            </span>
                                            <span v-if="tournament.location" class="text-xs text-muted-foreground md:hidden dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                                <MapPin class="h-3 w-3" />
                                                {{ tournament.location }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground dark:text-slate-400">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ formatDate(tournament.tournament_date) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground dark:text-slate-400">
                                            <MapPin class="h-3.5 w-3.5" />
                                            {{ tournament.location || '-' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="['capitalize font-normal', getStatusClass(tournament.status)]">
                                            {{ tournament.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Users class="h-3.5 w-3.5 text-muted-foreground dark:text-slate-400" />
                                            <span class="dark:text-slate-200">{{ tournament.registrations_count }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                size="sm" 
                                                :disabled="tournament.registrations_count < 2"
                                                @click="generate(tournament.id)"
                                                class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700 dark:text-white shadow-sm transition-all"
                                            >
                                                <Play class="h-3.5 w-3.5 mr-1.5 fill-current" />
                                                Generate
                                            </Button>
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                                class="hover:bg-slate-100 dark:hover:bg-slate-800 dark:text-slate-300"
                                            >
                                                <Link :href="route('admin.tournaments.brackets.show', tournament.id)">
                                                    View
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="filteredNotGenerated.length === 0">
                                    <TableCell colspan="5" class="h-24 text-center text-muted-foreground dark:text-slate-500">
                                        No pending tournaments found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Generated Brackets -->
                <Card class="shadow-sm dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800">
                        <CardTitle class="flex items-center gap-2 text-lg dark:text-slate-100">
                            <CheckCircle2 class="h-5 w-5 text-green-500" />
                            Generated Brackets
                        </CardTitle>
                        <CardDescription class="dark:text-slate-400">
                            Tournaments with active brackets.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10">
                                <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                    <TableHead class="w-[30%] font-semibold text-slate-500 dark:text-slate-400">Tournament</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-slate-500 dark:text-slate-400">Date</TableHead>
                                    <TableHead class="hidden md:table-cell font-semibold text-slate-500 dark:text-slate-400">Location</TableHead>
                                    <TableHead class="font-semibold text-slate-500 dark:text-slate-400">Status</TableHead>
                                    <TableHead class="text-center font-semibold text-slate-500 dark:text-slate-400">Registrations</TableHead>
                                    <TableHead class="text-right font-semibold text-slate-500 dark:text-slate-400">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="tournament in filteredGenerated" 
                                    :key="tournament.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-900/50 transition-colors border-b dark:border-slate-800"
                                >
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900 dark:text-slate-200">{{ tournament.name }}</span>
                                            <span class="text-xs text-muted-foreground md:hidden dark:text-slate-500 flex items-center gap-1">
                                                <Calendar class="h-3 w-3" />
                                                {{ formatDate(tournament.tournament_date) }}
                                            </span>
                                            <span v-if="tournament.location" class="text-xs text-muted-foreground md:hidden dark:text-slate-500 flex items-center gap-1 mt-0.5">
                                                <MapPin class="h-3 w-3" />
                                                {{ tournament.location }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground dark:text-slate-400">
                                            <Calendar class="h-3.5 w-3.5" />
                                            {{ formatDate(tournament.tournament_date) }}
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex items-center gap-2 text-muted-foreground dark:text-slate-400">
                                            <MapPin class="h-3.5 w-3.5" />
                                            {{ tournament.location || '-' }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline" :class="['capitalize font-normal', getStatusClass(tournament.status)]">
                                            {{ tournament.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Users class="h-3.5 w-3.5 text-muted-foreground dark:text-slate-400" />
                                            <span class="dark:text-slate-200">{{ tournament.registrations_count }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                :disabled="tournament.registrations_count < 2"
                                                @click="confirmRegenerate(tournament)"
                                                class="text-amber-600 hover:text-amber-700 hover:bg-amber-50 border-amber-200 dark:border-amber-900 dark:text-amber-500 dark:hover:bg-amber-900/20 shadow-sm transition-all"
                                                title="Regenerate Brackets"
                                            >
                                                <RefreshCw class="h-3.5 w-3.5 mr-1.5" />
                                                Regenerate
                                            </Button>
                                            <Button 
                                                variant="secondary" 
                                                size="sm" 
                                                as-child
                                                class="bg-slate-100 hover:bg-slate-200 text-slate-900 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 shadow-sm transition-all"
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
                                    <TableCell colspan="5" class="h-24 text-center text-muted-foreground dark:text-slate-500">
                                        No generated brackets found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Regenerate Dialog -->
        <Dialog v-model:open="isRegenerateDialogOpen">
            <DialogContent class="sm:max-w-md dark:bg-slate-950 dark:border-slate-800">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 dark:text-slate-100">
                        <RefreshCw class="h-5 w-5 text-amber-500" />
                        Regenerate Brackets
                    </DialogTitle>
                    <DialogDescription class="dark:text-slate-400">
                        Are you sure you want to regenerate the brackets for <strong>{{ tournamentToRegenerate?.name }}</strong>?
                        This will delete all current matches and scores. This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 sm:gap-0">
                    <Button 
                        variant="outline" 
                        @click="isRegenerateDialogOpen = false"
                        class="dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                    >
                        Cancel
                    </Button>
                    <Button 
                        variant="destructive" 
                        @click="handleRegenerate"
                        class="bg-amber-600 hover:bg-amber-700 text-white"
                    >
                        Regenerate
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
