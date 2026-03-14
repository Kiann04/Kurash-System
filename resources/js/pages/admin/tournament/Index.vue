<script setup lang="ts">
/**
 * Tournament Index Page
 * 
 * Displays a paginated list of tournaments with status indicators and management actions.
 * Allows creating new tournaments and deleting existing ones.
 */
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Trophy, 
    Calendar, 
    Plus, 
    Edit, 
    Trash2, 
    Eye,
    ChevronDown,
    Loader2,
    AlertCircle,
    Check
} from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';

// Components
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    DialogTrigger,
} from '@/components/ui/dialog';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

/**
 * Breadcrumbs configuration for the navigation bar
 */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
];

/**
 * Tournament interface matching the backend model
 */
interface Tournament {
    id: number;
    name: string;
    location: string | null;
    tournament_date: string;
    status: string;
}

/**
 * Props received from the Inertia controller
 * @property tournaments Paginated tournament data
 */
const props = defineProps<{
    tournaments: {
        data: Tournament[];
        links: any[];
    };
}>();

/**
 * State for the Create Tournament Modal
 */
const isAddTournamentModalOpen = ref(false);
const newTournament = ref({
    name: '',
    location: '',
    tournament_date: '',
    status: 'draft',
});

/**
 * State for the Delete Confirmation Modal
 */
const isDeleteModalOpen = ref(false)
const tournamentToDelete = ref<number | null>(null)
const isDeleting = ref(false)

/**
 * Validates input and redirects to the Create Tournament page with pre-filled data.
 * This initiates the multi-step creation process.
 */
const proceedToCreate = () => {
    if (!newTournament.value.name || !newTournament.value.tournament_date) {
        return;
    }

    // Close modal first
    isAddTournamentModalOpen.value = false;

    router.get(route('admin.tournaments.create'), {
        name: newTournament.value.name,
        location: newTournament.value.location,
        date: newTournament.value.tournament_date,
        status: newTournament.value.status,
    });
};

/**
 * Opens the delete confirmation modal for a specific tournament.
 * 
 * @param id The ID of the tournament to delete
 */
const deleteTournament = (id: number) => {
    tournamentToDelete.value = id
    isDeleteModalOpen.value = true
};

/**
 * Confirms and executes the tournament deletion via DELETE request.
 * Handles loading state and modal closure on success.
 */
const confirmDelete = () => {
    if (!tournamentToDelete.value) return

    isDeleting.value = true
    router.delete(route('admin.tournaments.destroy', tournamentToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteModalOpen.value = false
            tournamentToDelete.value = null
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        }
    });
};

/**
 * Formats a date string into a localized human-readable format.
 * 
 * @param date The ISO date string
 * @returns Formatted date string (e.g., "January 1, 2024")
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
 * 
 * @param status The tournament status (open, ongoing, completed, draft)
 * @returns CSS class string
 */
const getStatusColor = (status: string) => {
    switch(status.toLowerCase()) {
        case 'open': return 'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20';
        case 'ongoing': return 'bg-secondary/15 text-secondary hover:bg-secondary/25 border-secondary/20';
        case 'completed': return 'bg-primary/15 text-primary hover:bg-primary/25 border-primary/20';
        default: return 'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20'; // draft
    }
}
</script>

<template>
    <Head title="Tournaments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                        <Trophy class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Tournament Management</h1>
                        <p class="text-sm text-muted-foreground">
                            Create and manage Kurash tournaments.
                        </p>
                    </div>
                </div>

                <Dialog v-model:open="isAddTournamentModalOpen">
                    <DialogTrigger as-child>
                        <Button class="gap-2 shadow-sm">
                            <Plus class="h-4 w-4" />
                            Create Tournament
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Create New Tournament</DialogTitle>
                            <DialogDescription>
                                Enter the basic details to start setting up a new tournament.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="name">Tournament Name</Label>
                                <Input id="name" v-model="newTournament.name" placeholder="e.g. National Kurash Championship 2024" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="location">Location</Label>
                                <Input id="location" v-model="newTournament.location" placeholder="e.g. Manila Sports Complex" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="date">Tournament Date</Label>
                                <DatePicker id="date" v-model="newTournament.tournament_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label>Initial Status</Label>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" class="w-full justify-between font-normal">
                                            {{ newTournament.status === 'draft' ? 'Draft (Setup Mode)' : 'Open for Registration' }}
                                            <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width)">
                                        <DropdownMenuItem @click="newTournament.status = 'draft'" class="cursor-pointer">
                                            Draft (Setup Mode)
                                            <Check v-if="newTournament.status === 'draft'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="newTournament.status = 'open'" class="cursor-pointer">
                                            Open for Registration
                                            <Check v-if="newTournament.status === 'open'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isAddTournamentModalOpen = false">Cancel</Button>
                            <Button @click="proceedToCreate" :disabled="!newTournament.name || !newTournament.tournament_date">
                                Continue Setup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Delete Confirmation Modal -->
            <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
                <DialogContent class="sm:max-w-106.25">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2 text-destructive">
                            <AlertCircle class="h-5 w-5" />
                            Delete Tournament
                        </DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this tournament? This action cannot be undone and will remove all associated data including players, matches, and results.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" @click="isDeleteModalOpen = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="confirmDelete" :disabled="isDeleting">
                            <Loader2 v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
                            <Trash2 v-else class="mr-2 h-4 w-4" />
                            Delete Tournament
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Table -->
            <Card class="border-none bg-transparent shadow-none">
                <CardContent class="p-0">
                    <div class="relative w-full overflow-auto rounded-md border border-border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50 hover:bg-muted/50">
                                    <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground">Tournament Name</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground">Location</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground">Date</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-center text-muted-foreground">Status</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-right text-muted-foreground">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="t in tournaments.data"
                                    :key="t.id"
                                    class="hover:bg-muted/50 transition-colors border-b border-border"
                                >
                                    <TableCell class="p-4 align-middle font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-lg bg-muted flex items-center justify-center text-muted-foreground border border-border">
                                                <Trophy class="h-4.5 w-4.5" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-foreground font-medium">{{ t.name }}</span>
                                                <span class="text-xs text-muted-foreground font-mono">ID: #{{ t.id }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle">
                                        <span class="text-muted-foreground">{{ t.location || '-' }}</span>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle">
                                        <div class="flex items-center gap-2 text-muted-foreground">
                                            <Calendar class="h-4 w-4 text-white" />
                                            <span class="font-medium text-sm">{{ formatDate(t.tournament_date) }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-center">
                                    <Badge :class="['capitalize shadow-none font-normal w-20 justify-center border-transparent', getStatusColor(t.status)]">
                                            {{ t.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                                class="h-8 w-8 p-0 text-muted-foreground hover:text-primary hover:bg-primary/10 rounded-full"
                                                title="Edit Details"
                                            >
                                                <Link :href="route('admin.tournaments.edit', t.id)">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            
                                            <Button 
                                                v-if="route().has('admin.tournaments.show')"
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                                class="h-8 w-8 p-0 text-muted-foreground hover:text-secondary hover:bg-secondary/10 rounded-full"
                                                title="View Dashboard"
                                            >
                                                <Link :href="route('admin.tournaments.show', t.id)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>

                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-full"
                                                @click="deleteTournament(t.id)"
                                                title="Delete Tournament"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="tournaments.data.length === 0">
                                    <TableCell colspan="5" class="p-8 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <Trophy class="h-8 w-8 text-muted-foreground/50" />
                                            <p>No tournaments found. Create one to get started.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="mt-4">
                        <Pagination :links="tournaments.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
