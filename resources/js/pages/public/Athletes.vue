<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
const route = window.route;
import Pagination from '@/components/Pagination.vue'
import { Instagram, Facebook } from 'lucide-vue-next'

interface Player {
    id: number
    full_name: string
    gender: string
    club: string | null
    profile_image: string | null
}

interface Paginated<T> {
    data: T[]
    links: Array<{ url: string | null; label: string; active: boolean }>
}

const props = defineProps<{
    players: Paginated<Player>
}>()

const defaultProfileImage = '/images/default-profile.svg'

const navItems = [
    { name: 'Home', route: 'public.home' },
    { name: 'Anti-doping' },
    { name: 'Tournaments', route: 'public.tournaments.index' },
    { name: 'Rankings', route: 'public.rankings.index' },
    { name: 'Academies' },
    { name: 'Athletes', route: 'public.athletes.index' },
    { name: 'Rules' },
    { name: 'News' },
];
</script>

<template>
<Head title="Athletes | Kurash Federation" />
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

        <nav class="hidden lg:flex items-center gap-2 xl:gap-4 text-[10px] xl:text-xs font-bold tracking-widest uppercase h-full">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-4 transition-all duration-300 group whitespace-nowrap',
                item.route === 'public.athletes.index' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  item.route === 'public.athletes.index' ? 'w-full' : 'w-0 group-hover:w-full'
                ]"
              ></span>
            </a>
            <a 
              v-else
              href="#" 
              class="relative h-full flex items-center px-4 transition-all duration-300 group whitespace-nowrap text-gray-400 hover:text-white"
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
      <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-yellow-500/50 to-transparent"></div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 border-b border-slate-800 pb-8 gap-4">
            <div>
                <h2 class="text-5xl font-serif font-bold text-white mb-3">Our Athletes</h2>
                <p class="text-slate-400 text-lg">Official database of international Kurash competitors</p>
            </div>
            <div class="flex items-center gap-4 text-sm font-bold uppercase tracking-widest text-slate-500">
                <span>Total Athletes: {{ props.players.data.length }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <div v-for="player in props.players.data" :key="player.id" 
                 class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col">
                <div class="h-64 bg-slate-800 relative overflow-hidden">
                    <img 
                        :src="player.profile_image ? `/storage/${player.profile_image}` : defaultProfileImage" 
                        alt="Profile" 
                        class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-1000"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] to-transparent opacity-60"></div>
                    <div class="absolute bottom-4 left-6">
                        <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-1">{{ player.gender }}</div>
                        <h3 class="text-2xl font-serif font-bold text-white group-hover:text-yellow-500 transition-colors leading-tight">{{ player.full_name }}</h3>
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between">
                    <div class="flex items-center gap-3 text-slate-400 text-xs font-bold uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        {{ player.club ?? 'Independent' }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="props.players.data.length === 0" class="py-20 text-center">
            <p class="text-slate-500 italic text-xl">No athletes found in the database.</p>
        </div>

        <div class="mt-12">
            <Pagination :links="props.players.links" />
        </div>
    </main>
</div>
</template>
