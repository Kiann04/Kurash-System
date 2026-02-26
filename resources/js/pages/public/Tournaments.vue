<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
const route = window.route;
import Pagination from '@/components/Pagination.vue'
import { Instagram, Facebook, Calendar, MapPin, ExternalLink } from 'lucide-vue-next'

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

const navItems = [
    { name: 'Home', route: 'public.home' },
    { name: 'About' },
    { name: 'Anti-doping' },
    { name: 'Tournaments', route: 'public.tournaments.index' },
    { name: 'Rankings', route: 'public.rankings.index' },
    { name: 'Bracket', route: 'public.brackets.index' },
    { name: 'Academies' },
    { name: 'Athletes', route: 'public.athletes.index' },
    { name: 'Rules' },
    { name: 'News' },
];

const getStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
        case 'open': return 'text-green-500 bg-green-500/10 border-green-500/20';
        case 'ongoing': return 'text-yellow-500 bg-yellow-500/10 border-yellow-500/20';
        case 'completed': return 'text-gray-500 bg-gray-500/10 border-gray-500/20';
        default: return 'text-blue-500 bg-blue-500/10 border-blue-500/20';
    }
}
</script>

<template>
<Head title="Tournaments | Kurash Federation" />
<div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar (Same as Home.vue) -->
    <header class="border-b border-white/10 bg-[#050a14] relative z-50">
      <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
        <a :href="route('public.home')" class="flex items-center gap-3">
          <div class="font-black text-2xl tracking-tighter text-yellow-500 flex flex-col leading-none">
            <span>IKF</span>
          </div>
          <div class="h-8 w-px bg-white/20"></div>
          <div class="text-xs font-bold text-white leading-tight tracking-wide">
            INTERNATIONAL<br/>KURASH<br/>FEDERATION
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-[10px] xl:text-xs font-bold tracking-widest uppercase h-full">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-2 transition-all duration-300 group whitespace-nowrap',
                item.name === 'Tournaments' && item.route === 'public.tournaments.index' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  item.name === 'Tournaments' && item.route === 'public.tournaments.index' ? 'w-full' : 'w-0 group-hover:w-full'
                ]"
              ></span>
            </a>
            <a 
              v-else
              href="#" 
              class="relative h-full flex items-center px-2 transition-all duration-300 group whitespace-nowrap text-gray-400 hover:text-white"
            >
              {{ item.name }}
              <span class="absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)] w-0 group-hover:w-full"></span>
            </a>
          </template>
        </nav>

        <div class="flex items-center gap-5 shrink-0">
          <div class="flex items-center gap-4">
            <button class="text-gray-400 hover:text-yellow-500 transition-all duration-300 transform hover:scale-110 active:scale-95">
              <Instagram class="w-5 h-5" />
            </button>
            <button class="text-gray-400 hover:text-white transition-all duration-300 transform hover:scale-110 active:scale-95">
              <Facebook class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
      <div class="absolute bottom-0 left-0 w-full h-px bg-linear-to-r from-transparent via-yellow-500/50 to-transparent"></div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 border-b border-slate-800 pb-8 gap-4">
            <div>
                <h2 class="text-5xl font-serif font-bold text-white mb-3">Tournaments</h2>
                <p class="text-slate-400 text-lg">Official international Kurash tournaments and events</p>
            </div>
            <div class="flex items-center gap-4 text-sm font-bold uppercase tracking-widest text-slate-500">
                <span>Total Events: {{ props.tournaments.data.length }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div v-for="tournament in props.tournaments.data" :key="tournament.id" 
                 class="group bg-[#0f172a] rounded-4xl border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col p-8">
                
                <div class="flex justify-between items-start mb-6">
                    <div :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusColor(tournament.status)]">
                        {{ tournament.status }}
                    </div>
                    <Trophy class="w-6 h-6 text-yellow-500/50 group-hover:text-yellow-500 transition-colors" />
                </div>

                <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">
                    {{ tournament.name }}
                </h3>

                <div class="space-y-4 mb-8">
                    <div class="flex items-center gap-3 text-slate-400">
                        <Calendar class="w-4 h-4 text-yellow-500/50" />
                        <span class="text-sm font-medium tracking-wide">{{ tournament.tournament_date ?? 'TBD' }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-slate-400">
                        <MapPin class="w-4 h-4 text-yellow-500/50" />
                        <span class="text-sm font-medium tracking-wide">International Event</span>
                    </div>
                </div>

                <div class="mt-auto">
                    <a :href="route('public.tournaments.show', tournament.id)" 
                       class="inline-flex items-center gap-2 text-yellow-500 font-black text-xs uppercase tracking-[0.2em] group/link">
                        View Details
                        <ExternalLink class="w-3 h-3 transition-transform group-hover/link:translate-x-1" />
                    </a>
                </div>
            </div>
        </div>

        <div v-if="props.tournaments.data.length === 0" class="py-20 text-center">
            <p class="text-slate-500 italic text-xl">No upcoming tournaments found.</p>
        </div>

        <div class="mt-12">
            <Pagination :links="props.tournaments.links" />
        </div>
    </main>
</div>
</template>
