<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import Pagination from '@/components/Pagination.vue'

interface Tournament {
    id: number
    name: string
    tournament_date: string | null
    status: string
}

interface Paginated<T> {
    data: T[]
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    tournaments: Paginated<Tournament>
}>()
</script>

<template>
<Head title="Public Tournaments" />
<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-6xl px-6 py-10 space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold">Tournaments</h1>
            <Link :href="route('public.home')" class="text-sm text-blue-700 hover:underline">Back to public home</Link>
        </div>

        <div class="rounded-xl border bg-white overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in props.tournaments.data" :key="item.id" class="border-t">
                        <td class="p-3">{{ item.name }}</td>
                        <td class="p-3">{{ item.tournament_date ?? '-' }}</td>
                        <td class="p-3 capitalize">{{ item.status }}</td>
                        <td class="p-3">
                            <Link :href="route('public.tournaments.show', item.id)" class="text-blue-700 hover:underline">View</Link>
                        </td>
                    </tr>
                    <tr v-if="props.tournaments.data.length === 0">
                        <td colspan="4" class="p-4 text-center text-slate-500">No tournaments found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :links="props.tournaments.links" />
    </div>
</div>
</template>
