<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Players', href: route('admin.players.index') },
];

const props = defineProps<{
    players: { id: number; full_name: string; gender: string; age: number; weight: number }[];
}>();
</script>

<template>
    <Head title="Players" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">Players</h1>
                <Link
                    :href="route('admin.players.create')"
                    class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    Create Player
                </Link>
            </div>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div v-for="player in players" :key="player.id" class="p-4 border rounded-xl">
                    <h2 class="text-lg font-semibold">{{ player.full_name }}</h2>
                    <p>Gender: {{ player.gender }}</p>
                    <p>Age: {{ player.age }}</p>
                    <p>Weight: {{ player.weight }} kg</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>