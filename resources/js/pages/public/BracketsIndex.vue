<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}
const route = window.route || ((name: string) => name);
import { Instagram, Facebook, Calendar, Trophy, ChevronRight, Search, SlidersHorizontal } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import Pagination from '@/components/Pagination.vue'

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

const activeTab = ref('All')
const searchQuery = ref('')
const tabs = ['All', 'Upcoming', 'Completed']

const filteredTournaments = computed(() => {
    return props.tournaments.data.filter(tournament => {
        const search = searchQuery.value.toLowerCase()
        const matchesSearch = tournament.name.toLowerCase().includes(search)
        
        let matchesTab = true
        if (activeTab.value === 'Upcoming') {
            matchesTab = tournament.status.toLowerCase() === 'open' || tournament.status.toLowerCase() === 'ongoing'
        } else if (activeTab.value === 'Completed') {
            matchesTab = tournament.status.toLowerCase() === 'completed'
        }
        
        return matchesSearch && matchesTab
    })
})

const navItems = [
    { name: 'Home', route: 'public.home' },
    { name: 'Rankings', route: 'public.rankings.index' },
    { name: 'Tournaments', route: 'public.tournaments.index' },
    { name: 'Bracket', route: 'public.brackets.index' },
    { name: 'Athletes', route: 'public.athletes.index' },
    { name: 'Academies' },
    { name: 'Rules' },
    { name: 'Anti-Doping' },
    { name: 'News' },
    { name: 'About' },
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
      <div class="max-w-360 mx-auto px-8 h-20 flex items-center justify-between">
        <a :href="route('public.home')" class="flex items-center gap-3">
          <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
          <div class="h-8 w-px bg-white/20"></div>
          <div class="text-xs font-bold text-white leading-tight tracking-wide">
            KURASH<br/>SPORTS<br/>FEDERATION
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-x-2 xl:gap-x-4 text-[10px] xl:text-xs font-bold tracking-wider uppercase h-full font-serif">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap',
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
              class="relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap text-gray-400 hover:text-white"
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

        <!-- Header -->
        <div class="mb-12 relative z-10">
            <div class="flex flex-col items-center text-center gap-6 border-b border-slate-800 pb-12">
                <div class="max-w-4xl mx-auto flex flex-col items-center">
                    <div class="flex items-center gap-3 mb-4 justify-center">
                        <div class="h-px w-8 bg-yellow-500"></div>
                        <span class="text-yellow-500 font-black uppercase tracking-[0.3em] text-[10px]">Official Brackets</span>
                        <div class="h-px w-8 bg-yellow-500"></div>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-serif font-bold text-white mb-6 leading-tight tracking-tight uppercase">
                        Brackets <span class="text-yellow-500 italic">Board</span>
                    </h1>
                    <p class="text-slate-400 text-lg max-w-2xl leading-relaxed font-light">
                        Follow the path to glory. View elimination brackets, match results, and champions across all weight categories.
                    </p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12 bg-[#0f172a]/50 p-2 rounded-2xl border border-white/5 backdrop-blur-sm relative z-10">
            <!-- Tabs -->
            <div class="flex items-center bg-[#020617] rounded-xl p-1 gap-1 w-full md:w-auto overflow-x-auto">
                <button v-for="tab in tabs" :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all duration-300 whitespace-nowrap', 
                        activeTab === tab ? 'bg-yellow-500 text-black shadow-lg shadow-yellow-500/20' : 'text-slate-400 hover:text-white hover:bg-white/5'
                    ]">
                    {{ tab }}
                </button>
            </div>

            <!-- Search -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative group w-full md:w-64">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-focus-within:text-yellow-500 transition-colors" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search tournaments..." 
                        class="w-full bg-[#020617] border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-yellow-500/50 focus:ring-1 focus:ring-yellow-500/50 transition-all" 
                    />
                </div>
            </div>
        </div>

        <!-- Tournaments Grid -->
        <div v-if="filteredTournaments.length === 0" class="py-24 text-center border border-dashed border-slate-800 rounded-5xl">
            <Trophy class="w-16 h-16 text-slate-700 mx-auto mb-6 opacity-20" />
            <p class="text-slate-500 italic text-xl">No tournament brackets found.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <Link v-for="tournament in filteredTournaments" 
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
    <footer class="bg-[#020617] border-t-2 border-yellow-500/50 py-16">
        <div class="max-w-360 mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <!-- Brand Section -->
                <div class="md:col-span-2 space-y-6">
                    <a :href="route('public.home')" class="flex items-center gap-3">
                        <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
                        <div class="h-8 w-px bg-white/20"></div>
                        <div class="text-xs font-bold text-white leading-tight tracking-wide uppercase">
                            KURASH<br/>SPORTS<br/>FEDERATION
                        </div>
                    </a>
                    <p class="text-slate-400 text-sm max-w-sm leading-relaxed">
                        The Kurash Sports Federation is dedicated to the promotion, development, and management of Kurash sports globally, upholding the highest standards of integrity and competition.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-bold uppercase tracking-widest text-xs mb-6">Quick Links</h4>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a :href="route('public.home')" class="hover:text-yellow-500 transition-colors">Home</a></li>
                        <li><a :href="route('public.rankings.index')" class="hover:text-yellow-500 transition-colors">Rankings</a></li>
                        <li><a :href="route('public.tournaments.index')" class="hover:text-yellow-500 transition-colors">Tournaments</a></li>
                        <li><a href="#" class="hover:text-yellow-500 transition-colors">News</a></li>
                    </ul>
                </div>

                <!-- Legal & Contact -->
                <div>
                    <h4 class="text-white font-bold uppercase tracking-widest text-xs mb-6">Support</h4>
                    <ul class="space-y-4 text-sm text-slate-500">
                        <li><a href="#" class="hover:text-yellow-500 transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-yellow-500 transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-yellow-500 transition-colors">Contact</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6 text-slate-500 text-xs">
                <div>&copy; {{ new Date().getFullYear() }} Kurash Sports Federation. All rights reserved.</div>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-white transition-colors">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="hover:text-white transition-colors">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&display=swap');

.font-serif {
    font-family: 'Cinzel', serif;
}
.font-sophisticated {
    font-family: 'Playfair Display', serif;
}
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>