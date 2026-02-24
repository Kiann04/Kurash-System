<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Tournament {
    id: number
    name: string
    tournament_date: string | null
    status: string
}

interface Category {
    id: number
    gender: string | null
    age_category: string
    weight_category: string
    format: string
    matches_count: number
    completed_matches: number
}

const props = defineProps<{
    tournament: Tournament
    categories: Category[]
}>()
</script>

<template>
<Head :title="`Public: ${props.tournament.name}`" />
<div class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-6xl px-6 py-10 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ props.tournament.name }}</h1>
                <p class="text-slate-600">Date: {{ props.tournament.tournament_date ?? '-' }} | Status: {{ props.tournament.status }}</p>
            </div>
            <Link :href="route('public.tournaments.index')" class="text-sm text-blue-700 hover:underline">Back to tournaments</Link>
        </div>

        <div class="rounded-xl border bg-white overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3 text-left">Gender</th>
                        <th class="p-3 text-left">Age Category</th>
                        <th class="p-3 text-left">Weight Category</th>
                        <th class="p-3 text-left">Format</th>
                        <th class="p-3 text-left">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in props.categories" :key="item.id" class="border-t">
                        <td class="p-3 capitalize">{{ item.gender || '-' }}</td>
                        <td class="p-3">{{ item.age_category }}</td>
                        <td class="p-3">{{ item.weight_category }}</td>
                        <td class="p-3 capitalize">{{ item.format.replace('_', ' ') }}</td>
                        <td class="p-3">{{ item.completed_matches }} / {{ item.matches_count }} matches</td>
                    </tr>
                    <tr v-if="props.categories.length === 0">
                        <td colspan="5" class="p-4 text-center text-slate-500">No published bracket categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</template>
