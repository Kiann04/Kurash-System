<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'

interface Tournament {
    id: number
    name: string
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count: number
}

const props = defineProps<{
    tournaments: Tournament[]
}>()

const generate = (tournamentId: number) => {
    router.post(route('admin.tournaments.brackets.generate', tournamentId))
}
</script>

<template>
    <Head title="Generate Brackets" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <h1 class="text-2xl font-bold">Generate Brackets</h1>
            <p class="text-sm text-muted-foreground">
                2-5 players: round-robin, 6+ players: single elimination.
            </p>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th class="p-3 text-left">Tournament</th>
                            <th class="p-3 text-center">Date</th>
                            <th class="p-3 text-center">Status</th>
                            <th class="p-3 text-center">Registrations</th>
                            <th class="p-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tournament in props.tournaments" :key="tournament.id" class="border-t">
                            <td class="p-3 font-medium">{{ tournament.name }}</td>
                            <td class="p-3 text-center">{{ tournament.tournament_date }}</td>
                            <td class="p-3 text-center">{{ tournament.status }}</td>
                            <td class="p-3 text-center">{{ tournament.registrations_count }}</td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        class="px-3 py-1 rounded bg-blue-600 text-white disabled:opacity-50"
                                        :disabled="tournament.registrations_count < 2"
                                        @click="generate(tournament.id)"
                                    >
                                        Generate
                                    </button>
                                    <Link
                                        :href="route('admin.tournaments.brackets.show', tournament.id)"
                                        class="px-3 py-1 rounded border"
                                    >
                                        View
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
