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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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

    router.get(route('admin.tournaments.create'), {
        name: newTournament.value.name,
        date: newTournament.value.tournament_date,
        status: newTournament.value.status,
    });
};

const deleteTournament = (id: number) => {
    if (confirm('Are you sure you want to delete this tournament?')) {
        router.delete(route('admin.tournaments.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Tournaments" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">

            <!-- Header -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Tournament Dashboard</h1>
                    <p class="text-muted-foreground">
                        Manage and track your Kurash tournaments.
                    </p>
                </div>

                <Button @click="isAddTournamentModalOpen = true">
                    + Add Tournament
                </Button>
            </div>

            <!-- Add Tournament Modal -->
            <Dialog v-model:open="isAddTournamentModalOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>Add New Tournament</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Tournament Name</Label>
                            <Input id="name" v-model="newTournament.name" placeholder="Enter tournament name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="date">Tournament Date</Label>
                            <Input id="date" type="date" v-model="newTournament.tournament_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select id="status" v-model="newTournament.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                <option value="draft">Draft</option>
                                <option value="open">Open</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isAddTournamentModalOpen = false">Cancel</Button>
                        <Button @click="proceedToCreate" :disabled="!newTournament.name || !newTournament.tournament_date">
                            Continue
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Table -->
            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th class="text-left p-3">ID</th>
                            <th class="text-left p-3">Name</th>
                            <th class="text-left p-3">Date</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-right p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="t in tournaments.data"
                            :key="t.id"
                            class="border-t"
                        >
                            <td class="p-3 font-mono text-xs">{{ t.id }}</td>
                            <td class="p-3">{{ t.name }}</td>
                            <td class="p-3">{{ t.tournament_date }}</td>
                            <td class="p-3">{{ t.status }}</td>
                            <td class="p-3 text-right space-x-3">
                                <Link
                                    :href="route('admin.tournaments.show', t.id)"
                                    class="text-blue-600 hover:underline"
                                >
                                    View
                                </Link>

                                <Link
                                    :href="route('admin.tournaments.edit', t.id)"
                                    class="text-yellow-600 hover:underline"
                                >
                                    Edit
                                </Link>

                                <button
                                    @click="deleteTournament(t.id)"
                                    class="text-red-600 hover:underline"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="tournaments.data.length === 0">
                            <td colspan="4" class="p-6 text-center text-muted-foreground">
                                No tournaments found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="tournaments.links" />
        </div>
    </AppLayout>
</template>