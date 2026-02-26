<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { ref, computed } from 'vue'
import { Instagram, Facebook, Calendar, MapPin, ChevronLeft, Users, Scale, Flame, Clock, CheckCircle2, Swords } from 'lucide-vue-next'

interface Tournament {
    id: number
    name: string
    tournament_date: string | null
    status: string
}

interface MatchItem {
    id: number
    round_number: number
    match_number: number
    status: 'scheduled' | 'completed' | 'ongoing'
    player_one_id: number | null
    player_one: string | null
    player_one_club: string | null
    player_one_image: string | null
    player_two_id: number | null
    player_two: string | null
    player_two_club: string | null
    player_two_image: string | null
    winner_id: number | null
    winner: string | null
}

interface Category {
    id: number
    gender: string | null
    age_category: string
    weight_category: string
    format: 'round_robin' | 'single_elimination'
    rounds: number
    matches_count: number
    completed_matches: number
    champion: string | null
    matches: MatchItem[]
}

const props = defineProps<{
    tournament: Tournament
    categories: Category[]
}>()

const currentView = ref('male') // 'male' or 'female'

// Flatten all matches for the fight card view
const allMatches = computed(() => {
    return props.categories.flatMap(category => 
        category.matches.map(match => ({
            ...match,
            category_name: `${category.age_category} ${category.weight_category}`,
            gender: category.gender
        }))
    ).sort((a, b) => {
        // Group by status: ongoing first, then scheduled, then completed
        const statusOrder = { ongoing: 0, scheduled: 1, completed: 2 };
        if (statusOrder[a.status] !== statusOrder[b.status]) {
            return statusOrder[a.status] - statusOrder[b.status];
        }
        return a.match_number - b.match_number;
    });
});

const maleMatches = computed(() => allMatches.value.filter(m => m.gender?.toLowerCase() === 'male'));
const femaleMatches = computed(() => allMatches.value.filter(m => m.gender?.toLowerCase() === 'female'));

const getGroups = (matches: any[]) => {
    const ongoing = matches.filter(m => m.status === 'ongoing');
    const upcoming = matches.filter(m => m.status === 'scheduled');
    const finished = matches.filter(m => m.status === 'completed');

    return [
        { name: 'Live Now', matches: ongoing, icon: 'Flame' },
        { name: 'Upcoming Bouts', matches: upcoming, icon: 'Clock' },
        { name: 'Results', matches: finished, icon: 'CheckCircle2' }
    ].filter(group => group.matches.length > 0);
};

const maleFightCardGroups = computed(() => getGroups(maleMatches.value));
const femaleFightCardGroups = computed(() => getGroups(femaleMatches.value));

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
<Head :title="`${props.tournament.name} | Kurash Federation`" />
<div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar (Consistent across public pages) -->
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
                item.route === 'public.tournaments.index' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  item.route === 'public.tournaments.index' ? 'w-full' : 'w-0 group-hover:w-full'
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
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Back Button & Header -->
        <div class="mb-12">
            <a :href="route('public.tournaments.index')" class="inline-flex items-center gap-2 text-yellow-500/60 hover:text-yellow-500 font-bold text-[10px] uppercase tracking-widest transition-colors mb-8 group">
                <ChevronLeft class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
                Back to Tournaments
            </a>
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-800 pb-12">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusColor(tournament.status)]">
                            {{ tournament.status }}
                        </div>
                        <div class="h-px w-8 bg-slate-800"></div>
                        <div class="text-slate-500 font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                            <Trophy class="w-3 h-3 text-yellow-500/50" />
                            Official Event
                        </div>
                    </div>
                    <h1 class="text-5xl md:text-6xl font-serif font-bold text-white mb-6 leading-tight">{{ tournament.name }}</h1>
                    <div class="flex flex-wrap gap-8 text-slate-400">
                        <div class="flex items-center gap-3">
                            <Calendar class="w-5 h-5 text-yellow-500/50" />
                            <span class="text-sm font-medium tracking-wide">{{ tournament.tournament_date ?? 'TBD' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <MapPin class="w-5 h-5 text-yellow-500/50" />
                            <span class="text-sm font-medium tracking-wide">International Venue</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tournament Navigation Tabs -->
        <div class="mb-12 border-b border-slate-800">
            <div class="flex gap-12 overflow-x-auto no-scrollbar">
                <button 
                    @click="currentView = 'male'"
                    :class="[
                        'pb-6 text-sm font-black uppercase tracking-[0.2em] transition-all relative group',
                        currentView === 'male' ? 'text-blue-500' : 'text-slate-500 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <Users class="w-4 h-4" />
                        Male
                    </div>
                    <div :class="['absolute bottom-0 left-0 h-0.5 bg-blue-500 transition-all duration-300 shadow-[0_0_15px_rgba(59,130,246,0.5)]', currentView === 'male' ? 'w-full' : 'w-0 group-hover:w-full']"></div>
                </button>
                <button 
                    @click="currentView = 'female'"
                    :class="[
                        'pb-6 text-sm font-black uppercase tracking-[0.2em] transition-all relative group',
                        currentView === 'female' ? 'text-emerald-500' : 'text-slate-500 hover:text-white'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <Users class="w-4 h-4" />
                        Female
                    </div>
                    <div :class="['absolute bottom-0 left-0 h-0.5 bg-emerald-500 transition-all duration-300 shadow-[0_0_15px_rgba(16,185,129,0.5)]', currentView === 'female' ? 'w-full' : 'w-0 group-hover:w-full']"></div>
                </button>
            </div>
        </div>

        <!-- Male View -->
        <div v-if="currentView === 'male'" class="space-y-24">
            <!-- Fight Card Section -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/20 flex items-center justify-center border border-blue-600/30">
                        <Swords class="w-6 h-6 text-blue-500" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-white uppercase tracking-wider italic">Fight Card</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-blue-600/50 to-transparent"></div>
                </div>

                <div v-if="maleMatches.length === 0" class="py-16 text-center border border-dashed border-slate-800 rounded-5xl">
                    <p class="text-slate-500 italic text-lg">No male bouts scheduled yet.</p>
                </div>
                
                <div v-else v-for="group in maleFightCardGroups" :key="`male-${group.name}`" class="space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-slate-800 to-transparent"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-500 flex items-center gap-3">
                            <component :is="group.icon === 'Flame' ? Flame : (group.icon === 'Clock' ? Clock : CheckCircle2)" 
                                       :class="['w-4 h-4', group.icon === 'Flame' ? 'text-orange-500 animate-pulse' : 'text-slate-500']" />
                            {{ group.name }}
                        </h3>
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-slate-800 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="match in group.matches" :key="match.id" 
                             class="group relative bg-[#0f172a]/50 rounded-4xl border border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 overflow-hidden">
                            <!-- Card Background Text -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden">
                                <span class="text-[12rem] font-black text-white/2 italic uppercase tracking-tighter transform -rotate-12 group-hover:text-blue-500/3 transition-colors">VS</span>
                            </div>

                            <!-- Match Header Info -->
                            <div class="p-4 border-b border-white/5 bg-white/2 flex justify-between items-center relative z-10">
                                <div class="flex items-center gap-4">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-500/50">M{{ match.match_number }}</span>
                                    <div class="h-3 w-px bg-slate-800"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ match.category_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="match.status === 'ongoing'" class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-orange-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-ping"></span>
                                        Live
                                    </span>
                                    <span v-else class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        {{ match.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-12 relative z-10">
                                <!-- Player One -->
                                <div class="flex-1 flex flex-col md:flex-row items-center gap-8 w-full">
                                    <div class="relative group/photo order-2 md:order-1">
                                        <div class="w-32 h-44 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-blue-500/50 transition-all duration-500 shadow-2xl bg-slate-800">
                                            <img :src="match.player_one_image ? `/storage/${match.player_one_image}` : '/images/default-profile.svg'" 
                                                 class="w-full h-full object-cover grayscale group-hover/photo:grayscale-0 transition-all duration-700 group-hover/photo:scale-110" alt="Athlete" />
                                        </div>
                                        <div v-if="match.winner_id === match.player_one_id" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-black shadow-lg">
                                            <Trophy class="w-4 h-4" />
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left flex-1 order-1 md:order-2">
                                        <div class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-2">{{ match.player_one_club || 'INDEPENDENT' }}</div>
                                        <h4 class="text-3xl font-serif font-bold text-white group-hover:text-blue-400 transition-colors italic">{{ match.player_one || 'TBD' }}</h4>
                                    </div>
                                </div>

                                <!-- VS Divider -->
                                <div class="shrink-0 flex flex-col items-center gap-4">
                                    <div class="text-4xl font-serif font-black italic text-slate-700">VS</div>
                                </div>

                                <!-- Player Two -->
                                <div class="flex-1 flex flex-col md:flex-row-reverse items-center gap-8 w-full">
                                    <div class="relative group/photo">
                                        <div class="w-32 h-44 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-blue-500/50 transition-all duration-500 shadow-2xl bg-slate-800">
                                            <img :src="match.player_two_image ? `/storage/${match.player_two_image}` : '/images/default-profile.svg'" 
                                                 class="w-full h-full object-cover grayscale group-hover/photo:grayscale-0 transition-all duration-700 group-hover/photo:scale-110" alt="Athlete" />
                                        </div>
                                        <div v-if="match.winner_id === match.player_two_id" class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-black shadow-lg">
                                            <Trophy class="w-4 h-4" />
                                        </div>
                                    </div>
                                    <div class="text-center md:text-right flex-1">
                                        <div class="text-blue-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-2">{{ match.player_two_club || 'INDEPENDENT' }}</div>
                                        <h4 class="text-3xl font-serif font-bold text-white group-hover:text-blue-400 transition-colors italic">{{ match.player_two || 'TBD' }}</h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer Result -->
                            <div v-if="match.status === 'completed'" class="px-8 py-4 bg-yellow-500/5 border-t border-yellow-500/10 flex justify-center items-center relative z-10">
                                <div class="text-[10px] font-black uppercase tracking-[0.3em] text-yellow-500">
                                    Result: {{ match.winner || 'Draw/No Contest' }} won the bout
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Female View -->
        <div v-if="currentView === 'female'" class="space-y-24">
            <!-- Fight Card Section -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 flex items-center justify-center border border-emerald-600/30">
                        <Swords class="w-6 h-6 text-emerald-500" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-white uppercase tracking-wider italic">Fight Card</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-emerald-600/50 to-transparent"></div>
                </div>

                <div v-if="femaleMatches.length === 0" class="py-16 text-center border border-dashed border-slate-800 rounded-5xl">
                    <p class="text-slate-500 italic text-lg">No female bouts scheduled yet.</p>
                </div>
                
                <div v-else v-for="group in femaleFightCardGroups" :key="`female-${group.name}`" class="space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-slate-800 to-transparent"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-500 flex items-center gap-3">
                            <component :is="group.icon === 'Flame' ? Flame : (group.icon === 'Clock' ? Clock : CheckCircle2)" 
                                       :class="['w-4 h-4', group.icon === 'Flame' ? 'text-orange-500 animate-pulse' : 'text-slate-500']" />
                            {{ group.name }}
                        </h3>
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-slate-800 to-transparent"></div>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="match in group.matches" :key="match.id" 
                             class="group relative bg-[#0f172a]/50 rounded-4xl border border-slate-800/50 hover:border-emerald-500/30 transition-all duration-500 overflow-hidden">
                            <!-- Card Background Text -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden">
                                <span class="text-[12rem] font-black text-white/2 italic uppercase tracking-tighter transform -rotate-12 group-hover:text-emerald-500/3 transition-colors">VS</span>
                            </div>

                            <!-- Match Header Info -->
                            <div class="p-4 border-b border-white/5 bg-white/2 flex justify-between items-center relative z-10">
                                <div class="flex items-center gap-4">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-500/50">M{{ match.match_number }}</span>
                                    <div class="h-3 w-px bg-slate-800"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ match.category_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="match.status === 'ongoing'" class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-orange-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-ping"></span>
                                        Live
                                    </span>
                                    <span v-else class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        {{ match.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-12 relative z-10">
                                <!-- Player One -->
                                <div class="flex-1 flex flex-col md:flex-row items-center gap-8 w-full">
                                    <div class="relative group/photo order-2 md:order-1">
                                        <div class="w-32 h-44 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-emerald-500/50 transition-all duration-500 shadow-2xl bg-slate-800">
                                            <img :src="match.player_one_image ? `/storage/${match.player_one_image}` : '/images/default-profile.svg'" 
                                                 class="w-full h-full object-cover grayscale group-hover/photo:grayscale-0 transition-all duration-700 group-hover/photo:scale-110" alt="Athlete" />
                                        </div>
                                        <div v-if="match.winner_id === match.player_one_id" class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-black shadow-lg">
                                            <Trophy class="w-4 h-4" />
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left flex-1 order-1 md:order-2">
                                        <div class="text-emerald-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-2">{{ match.player_one_club || 'INDEPENDENT' }}</div>
                                        <h4 class="text-3xl font-serif font-bold text-white group-hover:text-emerald-400 transition-colors italic">{{ match.player_one || 'TBD' }}</h4>
                                    </div>
                                </div>

                                <!-- VS Divider -->
                                <div class="shrink-0 flex flex-col items-center gap-4">
                                    <div class="text-4xl font-serif font-black italic text-slate-700">VS</div>
                                </div>

                                <!-- Player Two -->
                                <div class="flex-1 flex flex-col md:flex-row-reverse items-center gap-8 w-full">
                                    <div class="relative group/photo">
                                        <div class="w-32 h-44 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-emerald-500/50 transition-all duration-500 shadow-2xl bg-slate-800">
                                            <img :src="match.player_two_image ? `/storage/${match.player_two_image}` : '/images/default-profile.svg'" 
                                                 class="w-full h-full object-cover grayscale group-hover/photo:grayscale-0 transition-all duration-700 group-hover/photo:scale-110" alt="Athlete" />
                                        </div>
                                        <div v-if="match.winner_id === match.player_two_id" class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-black shadow-lg">
                                            <Trophy class="w-4 h-4" />
                                        </div>
                                    </div>
                                    <div class="text-center md:text-right flex-1">
                                        <div class="text-emerald-400 font-bold text-[10px] uppercase tracking-[0.2em] mb-2">{{ match.player_two_club || 'INDEPENDENT' }}</div>
                                        <h4 class="text-3xl font-serif font-bold text-white group-hover:text-emerald-400 transition-colors italic">{{ match.player_two || 'TBD' }}</h4>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Footer Result -->
                            <div v-if="match.status === 'completed'" class="px-8 py-4 bg-yellow-500/5 border-t border-yellow-500/10 flex justify-center items-center relative z-10">
                                <div class="text-[10px] font-black uppercase tracking-[0.3em] text-yellow-500">
                                    Result: {{ match.winner || 'Draw/No Contest' }} won the bout
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="categories.length === 0" class="py-24 text-center border border-dashed border-slate-800 rounded-5xl">
            <p class="text-slate-500 italic text-xl mb-4">No published categories yet for this tournament.</p>
            <a :href="route('public.tournaments.index')" class="text-yellow-500 font-bold text-xs uppercase tracking-widest hover:underline">
                Check other tournaments
            </a>
        </div>
    </main>
</div>
</template>
