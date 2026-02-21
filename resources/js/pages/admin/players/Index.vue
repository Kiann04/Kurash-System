<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { ref, watch } from 'vue';
import { watchDebounced } from '@vueuse/core';
import PlayerFilters from './components/PlayerFilters.vue';
import PlayerTable from './components/PlayerTable.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Players', href: route('admin.players.index') },
];

interface Player {
    id: number;
    full_name: string;
    gender: string;
    age: number;
    club: string;
    address: string;
    membership_expires_at: string;
    status: string;
}

const props = defineProps<{
    players: {
        data: Player[];
        links: any[];
        from: number;
        to: number;
        total: number;
        last_page: number;
    };
    filters: {
        search?: string;
        gender?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const gender = ref(props.filters.gender || 'all');
const status = ref(props.filters.status || 'all');

watchDebounced(
    search,
    (value) => {
        router.get(
            route('admin.players.index'),
            { search: value, gender: gender.value, status: status.value },
            { preserveState: true, replace: true }
        );
    },
    { debounce: 300 }
);

watch([gender, status], ([newGender, newStatus]) => {
    router.get(
        route('admin.players.index'),
        { search: search.value, gender: newGender, status: newStatus },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Players" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header Section -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Player Dashboard</h1>
                    <p class="text-muted-foreground">Manage and track your Kurash players.</p>
                </div>
                <div class="flex items-center gap-4">
                    <PlayerFilters 
                        v-model:search="search"
                        v-model:gender="gender"
                    />
                    <Link :href="route('admin.players.create')">
                        <Button>
                            + Add Player
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b">
                <div class="flex gap-6">
                    <button 
                        class="pb-2 text-sm font-medium transition-colors"
                        :class="status === 'all' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'"
                        @click="status = 'all'"
                    >All Players</button>
                    <button 
                        class="pb-2 text-sm font-medium transition-colors"
                        :class="status === 'active' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'"
                        @click="status = 'active'"
                    >Active</button>
                    <button 
                        class="pb-2 text-sm font-medium transition-colors"
                        :class="status === 'inactive' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'"
                        @click="status = 'inactive'"
                    >Inactive / Renew</button>
                </div>
            </div>
            <!-- Players Table -->
            <PlayerTable :players="players" />
        </div>
    </AppLayout>
</template>
