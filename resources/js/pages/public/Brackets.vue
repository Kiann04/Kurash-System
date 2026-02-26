<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { ref, computed } from 'vue'
import { Instagram, Facebook, Trophy, Users, LayoutGrid, X } from 'lucide-vue-next'

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
const selectedCategory = ref<Category | null>(null)
const isModalOpen = ref(false)

const maleCategories = computed(() => props.categories.filter(c => c.gender?.toLowerCase() === 'male'));
const femaleCategories = computed(() => props.categories.filter(c => c.gender?.toLowerCase() === 'female'));

const openBracket = (category: Category) => {
    selectedCategory.value = category
    isModalOpen.value = true
}

const closeBracket = () => {
    isModalOpen.value = false
    selectedCategory.value = null
}

const roundsFor = (matches: MatchItem[]) => {
    const buckets: Record<number, MatchItem[]> = {}
    matches.forEach((match) => {
        if (!buckets[match.round_number]) buckets[match.round_number] = []
        buckets[match.round_number].push(match)
    })
    return Object.entries(buckets)
        .map(([round, items]) => ({
            round: Number(round),
            matches: [...items].sort((a, b) => a.match_number - b.match_number),
        }))
        .sort((a, b) => a.round - b.round)
}

const roundsForBracket = (category: Category) => roundsFor(category.matches)

const finalRoundNumber = (category: Category) => {
    const rounds = roundsForBracket(category)
    return rounds.length ? Math.max(...rounds.map((r) => r.round)) : 1
}

const eastRounds = (category: Category) => {
    const finalRound = finalRoundNumber(category)
    return roundsForBracket(category)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.filter((match) => match.match_number <= round.matches.length / 2),
        }))
}

const westRounds = (category: Category) => {
    const finalRound = finalRoundNumber(category)
    return roundsForBracket(category)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.filter((match) => match.match_number > round.matches.length / 2),
        }))
}

const grandFinalMatches = (category: Category) => {
    const finalRound = finalRoundNumber(category)
    const final = roundsForBracket(category).find((round) => round.round === finalRound)
    return final?.matches ?? []
}

const roundLabel = (totalRounds: number, roundNumber: number, conference?: 'East' | 'West') => {
    if (roundNumber === totalRounds) return 'Grand Final'
    const distance = totalRounds - roundNumber
    if (distance === 1) return conference ? `${conference} Final` : 'Final'
    if (distance === 2) return 'Semifinal'
    if (distance === 3) return 'Quarterfinal'
    return `Round ${roundNumber}`
}

const roundColumnStyle = (roundNumber: number) => {
    const multiplier = Math.max(1, Math.pow(2, roundNumber - 1))
    return {
        marginTop: `${(multiplier - 1) * 24}px`,
        rowGap: `${multiplier * 20}px`,
    }
}

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
<Head title="Brackets Board" />
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
                item.route === 'public.brackets.index' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  item.route === 'public.brackets.index' ? 'w-full' : 'w-0 group-hover:w-full'
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

        <!-- Header -->
        <div class="mb-12">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-800 pb-12">
                <div class="max-w-3xl">
                    <h1 class="text-5xl md:text-6xl font-serif font-bold text-white mb-6 leading-tight italic uppercase tracking-tighter">Brackets Board</h1>
                    <p class="text-slate-400 text-lg font-medium max-w-2xl leading-relaxed">
                        Follow the elimination paths and results for all weight categories. Select a category to view the full bracket layout.
                    </p>
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

    <!-- Content Area -->
    <div v-if="currentView === 'male'" class="space-y-24">
        <!-- Bracket Categories Grid -->
        <div class="space-y-16">
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/20 flex items-center justify-center border border-blue-600/30">
                    <LayoutGrid class="w-6 h-6 text-blue-500" />
                </div>
                <h2 class="text-4xl font-serif font-bold text-white uppercase tracking-wider italic">Categories</h2>
                <div class="h-px flex-1 bg-linear-to-r from-blue-600/50 to-transparent"></div>
            </div>

            <div v-if="maleCategories.length === 0" class="py-16 text-center border border-dashed border-slate-800 rounded-5xl">
                <p class="text-slate-500 italic text-lg">No male categories available.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                <div v-for="category in maleCategories" :key="category.id" 
                     class="group relative bg-[#0f172a]/50 rounded-4xl border border-slate-800/50 hover:border-blue-500/30 transition-all duration-500 p-8 flex flex-col gap-8 overflow-hidden">
                    
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <Trophy class="w-32 h-32 text-blue-500" />
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-500 text-[10px] font-black uppercase tracking-widest border border-blue-500/20">
                                {{ category.age_category }}
                            </span>
                            <div class="h-px w-4 bg-slate-800"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                {{ category.format.replace('_', ' ') }}
                            </span>
                        </div>
                        <h3 class="text-3xl font-serif font-bold text-white group-hover:text-blue-400 transition-colors italic">
                            {{ category.weight_category }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-4 relative z-10">
                        <div class="p-4 rounded-2xl bg-white/2 border border-white/5">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Matches</div>
                            <div class="text-xl font-serif font-bold text-white">{{ category.matches_count }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/2 border border-white/5">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Status</div>
                            <div class="text-xl font-serif font-bold text-white">{{ category.completed_matches }}/{{ category.matches_count }}</div>
                        </div>
                    </div>

                    <button 
                        @click="openBracket(category)"
                        class="w-full py-4 bg-white/5 hover:bg-blue-500 hover:text-black rounded-2xl border border-white/10 hover:border-blue-500 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                    >
                        View Bracket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Female View -->
    <div v-if="currentView === 'female'" class="space-y-24">
        <!-- Bracket Categories Grid -->
        <div class="space-y-16">
            <div class="flex items-center gap-6">
                <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 flex items-center justify-center border border-emerald-600/30">
                    <LayoutGrid class="w-6 h-6 text-emerald-500" />
                </div>
                <h2 class="text-4xl font-serif font-bold text-white uppercase tracking-wider italic">Categories</h2>
                <div class="h-px flex-1 bg-linear-to-r from-emerald-600/50 to-transparent"></div>
            </div>

            <div v-if="femaleCategories.length === 0" class="py-16 text-center border border-dashed border-slate-800 rounded-5xl">
                <p class="text-slate-500 italic text-lg">No female categories available.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                <div v-for="category in femaleCategories" :key="category.id" 
                     class="group relative bg-[#0f172a]/50 rounded-4xl border border-slate-800/50 hover:border-emerald-500/30 transition-all duration-500 p-8 flex flex-col gap-8 overflow-hidden">
                    
                    <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                        <Trophy class="w-32 h-32 text-emerald-500" />
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20">
                                {{ category.age_category }}
                            </span>
                            <div class="h-px w-4 bg-slate-800"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                {{ category.format.replace('_', ' ') }}
                            </span>
                        </div>
                        <h3 class="text-3xl font-serif font-bold text-white group-hover:text-emerald-400 transition-colors italic">
                            {{ category.weight_category }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-2 gap-4 relative z-10">
                        <div class="p-4 rounded-2xl bg-white/2 border border-white/5">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Matches</div>
                            <div class="text-xl font-serif font-bold text-white">{{ category.matches_count }}</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-white/2 border border-white/5">
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Status</div>
                            <div class="text-xl font-serif font-bold text-white">{{ category.completed_matches }}/{{ category.matches_count }}</div>
                        </div>
                    </div>

                    <button 
                        @click="openBracket(category)"
                        class="w-full py-4 bg-white/5 hover:bg-emerald-500 hover:text-black rounded-2xl border border-white/10 hover:border-emerald-500 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
                    >
                        View Bracket
                    </button>
                </div>
            </div>
        </div>
    </div>
    </main>

    <!-- Bracket Modal -->
    <div v-if="isModalOpen && selectedCategory" class="fixed inset-0 z-100 flex items-center justify-center p-4 md:p-8">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-[#050a14]/95 backdrop-blur-xl" @click="closeBracket"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-[95vw] h-full max-h-[95vh] bg-[#0f172a] rounded-4xl border border-white/10 shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="p-8 border-b border-white/10 flex items-center justify-between shrink-0 bg-white/2">
                <div class="flex items-center gap-8">
                    <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
                        <Trophy :class="['w-8 h-8', currentView === 'male' ? 'text-blue-500' : 'text-emerald-500']" />
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500">Tournament Bracket</span>
                            <div class="w-1 h-1 rounded-full bg-slate-700"></div>
                            <span :class="['text-[10px] font-black uppercase tracking-[0.3em]', currentView === 'male' ? 'text-blue-500' : 'text-emerald-500']">
                                {{ currentView }}
                            </span>
                        </div>
                        <h3 class="text-4xl font-serif font-bold text-white italic">
                            {{ selectedCategory.age_category }} {{ selectedCategory.weight_category }}
                        </h3>
                    </div>
                </div>
                <button @click="closeBracket" class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Modal Body (Bracket Board) -->
            <div class="flex-1 overflow-auto p-8 bg-[#050a14]/50">
                <!-- Single Elimination View -->
                <div v-if="selectedCategory.format === 'single_elimination'" class="min-w-max">
                    <div class="flex gap-12 justify-center items-start py-12">
                        <!-- East Side (Left) -->
                        <div class="flex gap-12">
                            <div v-for="round in eastRounds(selectedCategory)" :key="`east-${round.round}`" class="flex flex-col gap-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center mb-4">
                                    {{ roundLabel(selectedCategory.rounds, round.round, 'East') }}
                                </h4>
                                <div class="flex flex-col justify-around flex-1" :style="roundColumnStyle(round.round)">
                                    <div v-for="match in round.matches" :key="match.id" class="w-72">
                                        <!-- Match Card -->
                                        <div class="bg-white/2 border border-white/10 rounded-2xl p-4 hover:border-white/20 transition-all">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">M{{ match.match_number }}</span>
                                                <span v-if="match.status === 'ongoing'" class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                            </div>
                                            <div class="space-y-2">
                                                <div :class="['flex items-center justify-between p-2 rounded-lg', match.winner_id === match.player_one_id ? 'bg-yellow-500/10 border border-yellow-500/20' : 'bg-white/2']">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-slate-400']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                                <div :class="['flex items-center justify-between p-2 rounded-lg', match.winner_id === match.player_two_id ? 'bg-yellow-500/10 border border-yellow-500/20' : 'bg-white/2']">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-slate-400']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Final (Center) -->
                        <div class="flex flex-col gap-8">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-yellow-500 text-center mb-4">
                                {{ roundLabel(selectedCategory.rounds, selectedCategory.rounds) }}
                            </h4>
                            <div v-for="match in grandFinalMatches(selectedCategory)" :key="match.id" class="w-80">
                                <div class="bg-linear-to-b from-yellow-500/10 to-transparent border-2 border-yellow-500/20 rounded-3xl p-6 shadow-[0_0_50px_rgba(234,179,8,0.1)]">
                                    <div class="flex items-center justify-center gap-4 mb-6">
                                        <div class="h-px w-8 bg-yellow-500/20"></div>
                                        <Trophy class="w-6 h-6 text-yellow-500" />
                                        <div class="h-px w-8 bg-yellow-500/20"></div>
                                    </div>
                                    <div class="space-y-4">
                                        <div :class="['flex items-center justify-between p-4 rounded-2xl border transition-all', match.winner_id === match.player_one_id ? 'bg-yellow-500 border-yellow-400' : 'bg-white/5 border-white/10']">
                                            <div class="flex flex-col">
                                                <span :class="['text-sm font-black uppercase tracking-wide', match.winner_id === match.player_one_id ? 'text-black' : 'text-white']">
                                                    {{ match.player_one || 'TBD' }}
                                                </span>
                                                <span :class="['text-[10px] font-bold opacity-60', match.winner_id === match.player_one_id ? 'text-black' : 'text-slate-400']">
                                                    {{ match.player_one_club || 'Finalist' }}
                                                </span>
                                            </div>
                                            <Trophy v-if="match.winner_id === match.player_one_id" class="w-5 h-5 text-black" />
                                        </div>
                                        <div class="flex items-center justify-center">
                                            <span class="text-xs font-black text-slate-700 italic">VS</span>
                                        </div>
                                        <div :class="['flex items-center justify-between p-4 rounded-2xl border transition-all', match.winner_id === match.player_two_id ? 'bg-yellow-500 border-yellow-400' : 'bg-white/5 border-white/10']">
                                            <div class="flex flex-col">
                                                <span :class="['text-sm font-black uppercase tracking-wide', match.winner_id === match.player_two_id ? 'text-black' : 'text-white']">
                                                    {{ match.player_two || 'TBD' }}
                                                </span>
                                                <span :class="['text-[10px] font-bold opacity-60', match.winner_id === match.player_two_id ? 'text-black' : 'text-slate-400']">
                                                    {{ match.player_two_club || 'Finalist' }}
                                                </span>
                                            </div>
                                            <Trophy v-if="match.winner_id === match.player_two_id" class="w-5 h-5 text-black" />
                                        </div>
                                    </div>
                                    <div v-if="selectedCategory.champion" class="mt-8 p-4 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-center">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-yellow-500 mb-1">Champion</div>
                                        <div class="text-xl font-serif font-bold text-white italic">{{ selectedCategory.champion }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- West Side (Right) -->
                        <div class="flex gap-12">
                            <div v-for="round in [...westRounds(selectedCategory)].reverse()" :key="`west-${round.round}`" class="flex flex-col gap-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center mb-4">
                                    {{ roundLabel(selectedCategory.rounds, round.round, 'West') }}
                                </h4>
                                <div class="flex flex-col justify-around flex-1" :style="roundColumnStyle(round.round)">
                                    <div v-for="match in round.matches" :key="match.id" class="w-72">
                                        <!-- Match Card -->
                                        <div class="bg-white/2 border border-white/10 rounded-2xl p-4 hover:border-white/20 transition-all text-right">
                                            <div class="flex items-center justify-between mb-3 flex-row-reverse">
                                                <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">M{{ match.match_number }}</span>
                                                <span v-if="match.status === 'ongoing'" class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                                            </div>
                                            <div class="space-y-2">
                                                <div :class="['flex items-center justify-between p-2 rounded-lg flex-row-reverse', match.winner_id === match.player_one_id ? 'bg-yellow-500/10 border border-yellow-500/20' : 'bg-white/2']">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-slate-400']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                                <div :class="['flex items-center justify-between p-2 rounded-lg flex-row-reverse', match.winner_id === match.player_two_id ? 'bg-yellow-500/10 border border-yellow-500/20' : 'bg-white/2']">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-slate-400']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Round Robin View -->
                <div v-else class="max-w-4xl mx-auto py-12">
                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="match in selectedCategory.matches" :key="match.id" class="bg-white/2 border border-white/10 rounded-3xl p-6 flex items-center justify-between gap-8">
                            <div class="flex-1 text-right">
                                <div class="text-xs font-bold text-slate-500 mb-1">{{ match.player_one_club || 'Club' }}</div>
                                <div :class="['text-lg font-serif font-bold', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-white']">
                                    {{ match.player_one || 'TBD' }}
                                </div>
                            </div>
                            <div class="shrink-0 flex flex-col items-center gap-2">
                                <div class="text-[10px] font-black text-slate-700 uppercase tracking-widest">M{{ match.match_number }}</div>
                                <div class="text-2xl font-serif font-black italic text-slate-800">VS</div>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-slate-500 mb-1">{{ match.player_two_club || 'Club' }}</div>
                                <div :class="['text-lg font-serif font-bold', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-white']">
                                    {{ match.player_two || 'TBD' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>