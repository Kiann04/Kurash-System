<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { FileText, Download, Eye, Trophy, Users } from 'lucide-vue-next'

interface Tournament {
    id: number
    name: string
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count: number
    brackets_count: number
}

const props = defineProps<{
    tournaments: Tournament[]
}>()

const getStatusColor = (status: string) => {
    switch (status) {
        case 'draft': return 'bg-slate-100 text-slate-600 border-slate-200'
        case 'open': return 'bg-blue-100 text-blue-600 border-blue-200'
        case 'ongoing': return 'bg-amber-100 text-amber-600 border-amber-200'
        case 'completed': return 'bg-emerald-100 text-emerald-600 border-emerald-200'
        default: return 'bg-slate-100 text-slate-600 border-slate-200'
    }
}
</script>

<template>
    <Head title="Tournament Documentation" />

    <AppLayout>
        <div class="p-6 space-y-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tournament Documentation</h1>
                <p class="text-slate-500 mt-1">Access and print official tournament documents and reports.</p>
            </div>

            <div v-if="props.tournaments.length === 0" class="border rounded-xl p-12 text-center bg-white shadow-sm">
                <div class="bg-slate-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <FileText class="w-8 h-8 text-slate-400" />
                </div>
                <h3 class="text-lg font-medium text-slate-900">No tournaments found</h3>
                <p class="text-slate-500 mt-1">Create a tournament to start generating documentation.</p>
                <Link :href="route('admin.tournaments.index')" class="mt-4 inline-flex items-center text-blue-600 hover:underline font-medium">
                    Go to Tournaments
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div 
                    v-for="tournament in props.tournaments" 
                    :key="tournament.id" 
                    class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow"
                >
                    <div class="p-5 border-b bg-slate-50/50 flex justify-between items-start">
                        <div>
                            <h2 class="font-bold text-lg text-slate-900 leading-tight">{{ tournament.name }}</h2>
                            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                                {{ tournament.tournament_date }}
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border', getStatusColor(tournament.status)]">
                                    {{ tournament.status }}
                                </span>
                            </p>
                        </div>
                        <Trophy class="w-5 h-5 text-slate-400" />
                    </div>

                    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Bracket Document -->
                        <div class="p-4 rounded-lg border border-slate-100 bg-slate-50/30 flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <FileText class="w-4 h-4 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-slate-900">Tournament Brackets</h3>
                                    <p class="text-[11px] text-slate-500">{{ tournament.brackets_count }} categories generated</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-auto">
                                <Link 
                                    :href="route('admin.tournaments.brackets.show', tournament.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                                >
                                    <Eye class="w-3 h-3" />
                                    View & Print
                                </Link>
                            </div>
                        </div>

                        <!-- Participants Document -->
                        <div class="p-4 rounded-lg border border-slate-100 bg-slate-50/30 flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <Users class="w-4 h-4 text-emerald-600" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-bold text-slate-900">Participant List</h3>
                                    <p class="text-[11px] text-slate-500">{{ tournament.registrations_count }} athletes registered</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-auto">
                                <Link 
                                    :href="route('admin.tournaments.show', tournament.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                                >
                                    <Eye class="w-3 h-3" />
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
