<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Players', href: '/players' },
];

const props = defineProps<{
    players: {
        id: number;
        full_name: string;
        gender: string;
        age: number;
        weight: number;
    }[];
}>();
</script>

<template>
    <Head title="Players" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            
            <!-- Header + Create button -->
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Players</h1>
                <Link
                    href="/players/create"
                    class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    Create Player
                </Link>
            </div>

            <!-- Players Grid -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    v-for="player in players"
                    :key="player.id"
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4"
                >
                    <h2 class="text-lg font-semibold">{{ player.full_name }}</h2>
                    <p>Gender: {{ player.gender }}</p>
                    <p>Age: {{ player.age }}</p>
                    <p>Weight: {{ player.weight }}kg</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>