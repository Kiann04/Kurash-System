<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Tournament {
    id: number
    name: string
    tournament_date: string | null
    status: string
}

const props = defineProps<{
    stats: {
        total_tournaments: number
        open_tournaments: number
    }
    latest_tournaments: Tournament[]
}>()
</script>

<template>
<Head title="Kurash Public" />
<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-6xl px-6 py-10 space-y-8">
        <header class="space-y-2">
            <h1 class="text-3xl font-bold">Kurash Tournament Public Portal</h1>
            <p class="text-slate-600">Public view of tournaments and category brackets.</p>
        </header>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Total Tournaments</p>
                <p class="text-3xl font-semibold">{{ props.stats.total_tournaments }}</p>
            </div>
            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Open or Ongoing</p>
                <p class="text-3xl font-semibold">{{ props.stats.open_tournaments }}</p>
            </div>
        </div>

        <section class="rounded-xl border bg-white p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">Latest Tournaments</h2>
                <Link :href="route('public.tournaments.index')" class="text-sm text-blue-700 hover:underline">View all</Link>
            </div>

            <div class="overflow-x-auto">
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
                        <tr v-for="item in props.latest_tournaments" :key="item.id" class="border-t">
                            <td class="p-3">{{ item.name }}</td>
                            <td class="p-3">{{ item.tournament_date ?? '-' }}</td>
                            <td class="p-3 capitalize">{{ item.status }}</td>
                            <td class="p-3">
                                <Link :href="route('public.tournaments.show', item.id)" class="text-blue-700 hover:underline">Open</Link>
                            </td>
                        </tr>
                        <tr v-if="props.latest_tournaments.length === 0">
                            <td colspan="4" class="p-4 text-center text-slate-500">No tournaments available.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</template>
