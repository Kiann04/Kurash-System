<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { UserPlus, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
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
    membership_start_date: string;
    status: string;
}

const props = defineProps<{
    players: {
        data: Player[];
        links: any[];
    };
    filters: {
        search?: string;
        gender?: string;
        status?: string;
        membership_start?: string;
    };
}>();

const search = ref(props.filters.search || '');
const gender = ref(props.filters.gender || 'all');
const status = ref(props.filters.status || 'all');
const membership_start = ref(props.filters.membership_start || '');

watchDebounced(
    search,
    (value) => {
        router.get(
            route('admin.players.index'),
            { search: value, gender: gender.value, status: status.value, membership_start: membership_start.value },
            { preserveState: true, replace: true }
        );
    },
    { debounce: 300 }
);

watch([gender, status, membership_start], ([newGender, newStatus, newMembershipStart]) => {
    router.get(
        route('admin.players.index'),
        { search: search.value, gender: newGender, status: newStatus, membership_start: newMembershipStart },
        { preserveState: true, replace: true }
    );
});
</script>

<template>
    <Head title="Players" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100 dark:bg-blue-900/20 dark:border-blue-800">
                        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Player Management</h1>
                        <p class="text-sm text-muted-foreground">Monitor athlete registrations and membership status.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <PlayerFilters 
                        v-model:search="search"
                        v-model:gender="gender"
                        v-model:membership_start="membership_start"
                        class="flex-1 md:flex-none"
                    />
                    <Link :href="route('admin.players.create')">
                        <Button class="gap-2 shadow-sm bg-blue-600 hover:bg-blue-700 text-white dark:bg-blue-600 dark:hover:bg-blue-700">
                            <UserPlus class="h-4 w-4" />
                            Add Player
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="w-full border-b border-slate-200 dark:border-slate-800">
                <div class="flex gap-6 overflow-x-auto">
                    <button 
                        v-for="tab in [
                            { id: 'all', label: 'All Players' },
                            { id: 'active', label: 'Active Members' },
                            { id: 'expiring_soon', label: 'Expiring Soon' },
                            { id: 'inactive', label: 'Inactive Members' }
                        ]"
                        :key="tab.id"
                        class="relative pb-3 text-sm font-medium transition-all whitespace-nowrap"
                        :class="status === tab.id ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        @click="status = tab.id"
                    >
                        {{ tab.label }}
                        <span 
                            v-if="status === tab.id" 
                            class="absolute bottom-0 left-0 h-0.5 w-full bg-blue-600 dark:bg-blue-400 rounded-t-full"
                        ></span>
                    </button>
                </div>
            </div>

            <!-- Players Table -->
            <PlayerTable :players="players" />
        </div>
    </AppLayout>
</template>
