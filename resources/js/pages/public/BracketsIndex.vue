<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}
const route = window.route || ((name: string) => name);
import Pagination from '@/components/Pagination.vue'
import { Instagram, Facebook, Calendar, Trophy, ChevronRight } from 'lucide-vue-next'

interface Tournament {
    id: number
    name: string
    tournament_date: string | null
    status: string
    brackets_count: number
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
<Head title="Brackets Board | Kurash Federation" />
<div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar -->
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
                (item.route === 'public.brackets.index' || item.route === 'public.brackets.show') ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  (item.route === 'public.brackets.index' || item.route === 'public.brackets.show') ? 'w-full' : 'w-0 group-hover:w-full'
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

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-16 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Page Header -->
        <div class="mb-16 relative">
            <div class="flex items-center gap-3 text-yellow-500 font-black uppercase tracking-[0.3em] text-[10px] mb-4">
                <div class="h-px w-8 bg-yellow-500"></div>
                Tournament Brackets
            </div>
            <h1 class="text-5xl md:text-7xl font-serif font-bold text-white mb-6 tracking-tight">
                BRACKETS <span class="text-yellow-500 italic text-4xl md:text-6xl">BOARD</span>
            </h1>
            <p class="text-slate-400 text-lg max-w-2xl leading-relaxed font-light">
                Select a tournament below to view the full elimination brackets, match results, and champions for each weight category.
            </p>
        </div>

        <!-- Tournaments Grid -->
        <div v-if="tournaments.data.length === 0" class="py-24 text-center border border-dashed border-slate-800 rounded-5xl">
            <Trophy class="w-16 h-16 text-slate-700 mx-auto mb-6 opacity-20" />
            <p class="text-slate-500 italic text-xl">No tournament brackets available yet.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <Link v-for="tournament in tournaments.data" 
                  :key="tournament.id"
                  :href="route('public.brackets.show', tournament.id)"
                  class="group relative bg-[#0f172a]/40 rounded-4xl border border-slate-800/50 hover:border-yellow-500/30 transition-all duration-500 overflow-hidden flex flex-col h-full shadow-2xl hover:shadow-yellow-500/5"
            >
                <!-- Card Header/Image Area -->
                <div class="h-48 bg-slate-800/50 relative overflow-hidden">
                    <div class="absolute inset-0 bg-linear-to-br from-blue-900/20 to-transparent"></div>
                    <div class="absolute inset-0 flex items-center justify-center opacity-10 group-hover:opacity-20 transition-opacity duration-500">
                        <Trophy class="w-32 h-32 text-yellow-500" />
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="absolute top-6 right-6">
                        <span :class="['px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusColor(tournament.status)]">
                            {{ tournament.status }}
                        </span>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-8 flex-1 flex flex-col">
                    <div class="flex items-center gap-2 text-yellow-500/60 font-bold text-[10px] uppercase tracking-[0.2em] mb-4">
                        <Calendar class="w-3.5 h-3.5" />
                        {{ tournament.tournament_date }}
                    </div>
                    
                    <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">
                        {{ tournament.name }}
                    </h3>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mt-auto">
                        <div class="bg-white/5 rounded-2xl p-4 border border-white/5">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Categories</div>
                            <div class="text-xl font-bold text-white">{{ tournament.brackets_count }}</div>
                        </div>
                        <div class="bg-white/5 rounded-2xl p-4 border border-white/5 flex items-end justify-between group/btn">
                            <div class="text-[10px] font-black text-yellow-500 uppercase tracking-widest mb-1">View</div>
                            <ChevronRight class="w-5 h-5 text-yellow-500 transform group-hover/btn:translate-x-1 transition-transform" />
                        </div>
                    </div>
                </div>

                <!-- Hover Decor -->
                <div class="absolute bottom-0 left-0 w-0 h-1 bg-yellow-500 transition-all duration-500 group-hover:w-full"></div>
            </Link>
        </div>

        <!-- Pagination -->
        <div class="mt-16 flex justify-center">
            <Pagination :links="tournaments.links" />
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-[#020617] border-t border-white/5 py-12 mt-24">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="text-slate-500 text-sm">&copy; 2024 International Kurash Federation. All rights reserved.</div>
            <div class="flex gap-8 text-sm font-bold tracking-widest uppercase">
                <a href="#" class="text-slate-400 hover:text-yellow-500 transition-colors">Privacy</a>
                <a href="#" class="text-slate-400 hover:text-yellow-500 transition-colors">Terms</a>
                <a href="#" class="text-slate-400 hover:text-yellow-500 transition-colors">Contact</a>
            </div>
        </div>
    </footer>
</div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@300;400;500;600;700&display=swap');

.font-serif {
    font-family: 'Cinzel', serif;
}
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>