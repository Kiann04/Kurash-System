<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';

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

                <Link :href="route('admin.tournaments.create')">
                    <Button>
                        + Add Tournament
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
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

        </div>
    </AppLayout>
</template>