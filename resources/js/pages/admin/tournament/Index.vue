<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import Pagination from '@/components/Pagination.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { 
    Trophy, 
    Calendar, 
    Plus, 
    MoreHorizontal, 
    Edit, 
    Trash2, 
    Eye 
} from 'lucide-vue-next';

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
    if (confirm('Are you sure you want to delete this tournament? This action cannot be undone.')) {
        router.delete(route('admin.tournaments.destroy', id), {
            preserveScroll: true,
        });
    }
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
                    <div class="h-10 w-10 rounded-lg bg-yellow-500/10 flex items-center justify-center dark:bg-yellow-500/20">
                        <Trophy class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
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
                        <Button class="gap-2 shadow-sm">
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
                                <Label for="status">Initial Status</Label>
                                <select 
                                    id="status" 
                                    v-model="newTournament.status" 
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                >
                                    <option value="draft">Draft (Setup Mode)</option>
                                    <option value="open">Open for Registration</option>
                                </select>
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

            <!-- Table -->
            <Card class="border-none shadow-none bg-white dark:bg-slate-950">
                <CardContent class="p-0">
                    <div class="rounded-md border dark:border-slate-800">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50 hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-900">
                                    <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Tournament Name</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Date</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-center dark:text-slate-400">Status</TableHead>
                                    <TableHead class="h-12 px-4 align-middle font-medium text-right dark:text-slate-400">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="t in tournaments.data"
                                    :key="t.id"
                                    class="hover:bg-slate-50/50 transition-colors dark:hover:bg-slate-900/50 dark:border-slate-800"
                                >
                                    <TableCell class="p-4 align-middle font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                <Trophy class="h-4 w-4" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-slate-900 dark:text-slate-100">{{ t.name }}</span>
                                                <span class="text-xs text-slate-500 dark:text-slate-500">ID: #{{ t.id }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle">
                                        <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                            <Calendar class="h-4 w-4 text-slate-400 dark:text-slate-500" />
                                            <span>{{ formatDate(t.tournament_date) }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-center">
                                        <Badge :class="['capitalize shadow-none font-normal', getStatusColor(t.status)]">
                                            {{ t.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="p-4 align-middle text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" class="h-8 w-8 p-0">
                                                    <span class="sr-only">Open menu</span>
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="dark:bg-slate-950 dark:border-slate-800">
                                                <DropdownMenuLabel class="dark:text-slate-200">Actions</DropdownMenuLabel>
                                                <Link :href="route('admin.tournaments.edit', t.id)">
                                                    <DropdownMenuItem class="dark:focus:bg-slate-800 dark:focus:text-slate-100">
                                                        <Edit class="mr-2 h-4 w-4" />
                                                        Edit Details
                                                    </DropdownMenuItem>
                                                </Link>
                                                <!-- Assuming there is a show route or public view -->
                                                <Link :href="route('admin.tournaments.show', t.id)" v-if="route().has('admin.tournaments.show')">
                                                    <DropdownMenuItem class="dark:focus:bg-slate-800 dark:focus:text-slate-100">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View Dashboard
                                                    </DropdownMenuItem>
                                                </Link>
                                                <DropdownMenuSeparator class="dark:bg-slate-800" />
                                                <DropdownMenuItem 
                                                    @click="deleteTournament(t.id)"
                                                    class="text-red-600 focus:text-red-700 focus:bg-red-50 dark:text-red-400 dark:focus:bg-red-900/30 dark:focus:text-red-300"
                                                >
                                                    <Trash2 class="mr-2 h-4 w-4" />
                                                    Delete Tournament
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
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
