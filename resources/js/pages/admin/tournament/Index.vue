<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Trophy, 
    Calendar, 
    Plus, 
    MoreHorizontal, 
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
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
];

interface Tournament {
    id: number;
    name: string;
    tournament_date: string;
    status: string;
}

const props = defineProps<{
    tournaments: {
        data: Tournament[];
        links: any[];
    };
}>();

const isAddTournamentModalOpen = ref(false);
const newTournament = ref({
    name: '',
    tournament_date: '',
    status: 'draft',
});

// Delete Tournament State
const isDeleteModalOpen = ref(false)
const tournamentToDelete = ref<number | null>(null)
const isDeleting = ref(false)

const proceedToCreate = () => {
    if (!newTournament.value.name || !newTournament.value.tournament_date) {
        return;
    }

    // Close modal first
    isAddTournamentModalOpen.value = false;

    router.get(route('admin.tournaments.create'), {
        name: newTournament.value.name,
        date: newTournament.value.tournament_date,
        status: newTournament.value.status,
    });
};

const deleteTournament = (id: number) => {
    tournamentToDelete.value = id
    isDeleteModalOpen.value = true
};

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

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getStatusColor = (status: string) => {
    switch(status.toLowerCase()) {
        case 'open': return 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50';
        case 'ongoing': return 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50';
        case 'completed': return 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700';
        default: return 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50'; // draft
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
                    <div class="h-12 w-12 rounded-xl bg-indigo-50 flex items-center justify-center border border-indigo-100 dark:bg-indigo-900/20 dark:border-indigo-800">
                        <Trophy class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Tournament Management</h1>
                        <p class="text-sm text-muted-foreground">
                            Create and manage Kurash tournaments.
                        </p>
                    </div>
                </div>

                <Dialog v-model:open="isAddTournamentModalOpen">
                    <DialogTrigger as-child>
                        <Button class="gap-2 shadow-sm bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700">
                            <Plus class="h-4 w-4" />
                            Create Tournament
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md dark:bg-slate-950 dark:border-slate-800">
                        <DialogHeader>
                            <DialogTitle class="dark:text-slate-100">Create New Tournament</DialogTitle>
                            <DialogDescription class="dark:text-slate-400">
                                Enter the basic details to start setting up a new tournament.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="name" class="dark:text-slate-300">Tournament Name</Label>
                                <Input id="name" v-model="newTournament.name" placeholder="e.g. National Kurash Championship 2024" class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="date" class="dark:text-slate-300">Tournament Date</Label>
                                <Input id="date" type="date" v-model="newTournament.tournament_date" class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100" />
                            </div>
                            <div class="grid gap-2">
                                <Label class="dark:text-slate-300">Initial Status</Label>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 font-normal">
                                            {{ newTournament.status === 'draft' ? 'Draft (Setup Mode)' : 'Open for Registration' }}
                                            <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800">
                                        <DropdownMenuItem @click="newTournament.status = 'draft'" class="dark:text-slate-100 cursor-pointer">
                                            Draft (Setup Mode)
                                            <Check v-if="newTournament.status === 'draft'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="newTournament.status = 'open'" class="dark:text-slate-100 cursor-pointer">
                                            Open for Registration
                                            <Check v-if="newTournament.status === 'open'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" @click="isAddTournamentModalOpen = false" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Cancel</Button>
                            <Button @click="proceedToCreate" :disabled="!newTournament.name || !newTournament.tournament_date" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700">
                                Continue Setup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Delete Confirmation Modal -->
            <Dialog :open="isDeleteModalOpen" @update:open="isDeleteModalOpen = $event">
                <DialogContent class="sm:max-w-106.25 dark:bg-slate-950 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2 text-destructive dark:text-red-500">
                            <AlertCircle class="h-5 w-5" />
                            Delete Tournament
                        </DialogTitle>
                        <DialogDescription class="dark:text-slate-400">
                            Are you sure you want to delete this tournament? This action cannot be undone and will remove all associated data including players, matches, and results.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button variant="outline" @click="isDeleteModalOpen = false" class="dark:border-slate-800 dark:text-slate-300">
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
            <Card class="border shadow-sm bg-white dark:bg-slate-950 dark:border-slate-800 overflow-hidden">
                <CardContent class="p-0">
                    <div class="rounded-none border-0">
                        <Table>
                            <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm">
                                <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                    <TableHead class="h-12 px-4 align-middle font-semibold text-slate-500 dark:text-slate-400">Tournament Name</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-semibold text-slate-500 dark:text-slate-400">Date</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-semibold text-center text-slate-500 dark:text-slate-400">Status</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-semibold text-right text-slate-500 dark:text-slate-400">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="t in tournaments.data"
                                    :key="t.id"
                                    class="hover:bg-slate-50/50 transition-colors dark:hover:bg-slate-900/50 dark:border-slate-800 border-b last:border-0"
                                >
                                    <TableCell class="p-4 align-middle font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                                <Trophy class="h-4.5 w-4.5" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-slate-900 font-semibold dark:text-slate-100">{{ t.name }}</span>
                                                <span class="text-xs text-slate-500 font-mono dark:text-slate-500">ID: #{{ t.id }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle">
                                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                            <Calendar class="h-4 w-4 text-slate-400 dark:text-slate-500" />
                                            <span class="font-medium text-sm">{{ formatDate(t.tournament_date) }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-center">
                                        <Badge :class="['capitalize shadow-sm font-medium border-transparent', getStatusColor(t.status)]">
                                            {{ t.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                as-child
                                                class="h-8 w-8 p-0 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:text-slate-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/30 rounded-full"
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
                                                class="h-8 w-8 p-0 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/30 rounded-full"
                                                title="View Dashboard"
                                            >
                                                <Link :href="route('admin.tournaments.show', t.id)">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>

                                            <Button 
                                                variant="ghost" 
                                                size="sm" 
                                                class="h-8 w-8 p-0 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-900/30 rounded-full"
                                                @click="deleteTournament(t.id)"
                                                title="Delete Tournament"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="tournaments.data.length === 0">
                                    <TableCell colspan="4" class="p-8 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <Trophy class="h-8 w-8 text-slate-300 dark:text-slate-600" />
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
