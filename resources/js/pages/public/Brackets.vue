<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { ref, computed } from 'vue'
import { Instagram, Facebook, Trophy, Users, LayoutGrid, X, ArrowLeft, Search, SlidersHorizontal } from 'lucide-vue-next'

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
    entrant_count?: number
    matches_count: number
    completed_matches: number
    champion: string | null
    bronze_match?: MatchItem | null
    matches: MatchItem[]
}

const props = defineProps<{
    tournament: Tournament
    categories: Category[]
}>()

const selectedCategory = ref<Category | null>(null)
const isModalOpen = ref(false)
const activeGenderTab = ref('All')
const searchQuery = ref('')
const genderTabs = ['All', 'Male', 'Female']

const filteredCategories = computed(() => {
    return props.categories.filter(category => {
        const search = searchQuery.value.toLowerCase()
        const matchesSearch = category.weight_category.toLowerCase().includes(search) || 
                            category.age_category.toLowerCase().includes(search)
        
        let matchesGender = true
        if (activeGenderTab.value !== 'All') {
            matchesGender = category.gender?.toLowerCase() === activeGenderTab.value.toLowerCase()
        }
        
        return matchesSearch && matchesGender
    })
})

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

const roundsForBracket = (category: Category | null) => {
    if (!category) return []
    return roundsFor(category.matches)
}

const finalRoundNumber = (category: Category | null) => {
    if (!category) return 1
    const rounds = roundsForBracket(category)
    return rounds.length ? Math.max(...rounds.map((r) => r.round)) : 1
}

const eastRounds = (category: Category | null) => {
    if (!category) return []
    const finalRound = finalRoundNumber(category)
    return roundsForBracket(category)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.slice(0, Math.ceil(round.matches.length / 2)),
        }))
}

const westRounds = (category: Category | null) => {
    if (!category) return []
    const finalRound = finalRoundNumber(category)
    return roundsForBracket(category)
        .filter((round) => round.round < finalRound)
        .map((round) => ({
            round: round.round,
            matches: round.matches.slice(Math.ceil(round.matches.length / 2)),
        }))
}

const grandFinalMatches = (category: Category | null) => {
    if (!category) return []
    const finalRound = finalRoundNumber(category)
    const final = roundsForBracket(category).find((round) => round.round === finalRound)
    return final?.matches ?? []
}

const bracketSize = (entrants: number | undefined, totalRounds: number) => {
    if (entrants && entrants > 0) {
        let size = 1
        while (size < entrants) size *= 2
        return size
    }
    return Math.pow(2, totalRounds)
}

const roundLabel = (
    totalRounds: number,
    roundNumber: number,
    entrants?: number,
    format?: Category['format']
) => {
    if (format !== 'single_elimination') {
        return `Round ${roundNumber}`
    }
    const size = bracketSize(entrants, totalRounds)
    const remaining = size / Math.pow(2, roundNumber - 1)
    if (remaining <= 2) return `Final (${remaining} -> ${remaining / 2})`
    if (remaining === 4) return 'Semifinal (4 -> 2)'
    if (remaining === 8) return 'Quarterfinal (8 -> 4)'
    if (remaining >= 16) return `Round of ${remaining} (${remaining} -> ${remaining / 2})`
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
<Head title="Brackets Board" />
<div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar (Consistent across public pages) -->
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

    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Header -->
        <div class="mb-12 relative z-10">
            <div class="flex flex-col items-center text-center gap-6 border-b border-slate-800 pb-12">
                <div class="max-w-4xl mx-auto flex flex-col items-center">
                    <a :href="route('public.brackets.index')" class="flex items-center gap-2 text-yellow-500/60 hover:text-yellow-500 font-bold text-[10px] uppercase tracking-[0.2em] mb-6 transition-colors group w-fit">
                        <ArrowLeft class="w-3.5 h-3.5 transform group-hover:-translate-x-1 transition-transform" />
                        Back to Tournaments
                    </a>
                    <div class="flex items-center gap-3 mb-6 justify-center">
                        <div :class="['px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusColor(tournament.status)]">
                            {{ tournament.status }}
                        </div>
                        <div class="h-px w-8 bg-slate-800"></div>
                        <div class="text-slate-500 font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                            <Trophy class="w-3 h-3 text-yellow-500/50" />
                            Official Event
                        </div>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-serif font-bold text-white mb-6 leading-tight tracking-tight">{{ tournament.name }}</h1>
                    <p class="text-slate-400 text-lg max-w-2xl leading-relaxed font-light">
                        Follow the elimination paths and results for all weight categories. Select a category to view the full bracket layout.
                    </p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12 bg-[#0f172a]/50 p-2 rounded-2xl border border-white/5 backdrop-blur-sm relative z-10">
            <!-- Tabs -->
            <div class="flex items-center bg-[#020617] rounded-xl p-1 gap-1 w-full md:w-auto overflow-x-auto">
                <button v-for="tab in genderTabs" :key="tab"
                    @click="activeGenderTab = tab"
                    :class="[
                        'px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all duration-300 whitespace-nowrap', 
                        activeGenderTab === tab ? 'bg-yellow-500 text-black shadow-lg shadow-yellow-500/20' : 'text-slate-400 hover:text-white hover:bg-white/5'
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
                        placeholder="Search categories..." 
                        class="w-full bg-[#020617] border border-white/10 rounded-xl py-2.5 pl-10 pr-4 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-yellow-500/50 focus:ring-1 focus:ring-yellow-500/50 transition-all" 
                    />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="space-y-24">
            <!-- Bracket Categories Grid -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl bg-yellow-600/20 flex items-center justify-center border border-yellow-600/30">
                        <LayoutGrid class="w-6 h-6 text-yellow-500" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-white uppercase tracking-wider italic">Categories</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-yellow-600/50 to-transparent"></div>
                </div>

                <div v-if="filteredCategories.length === 0" class="py-16 text-center border border-dashed border-slate-800 rounded-5xl">
                    <p class="text-slate-500 italic text-lg">No categories found matching your criteria.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <div v-for="category in filteredCategories" :key="category.id" 
                         class="group relative bg-[#0f172a]/50 rounded-4xl border border-slate-800/50 hover:border-yellow-500/30 transition-all duration-500 p-8 flex flex-col gap-8 overflow-hidden">
                        
                        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                            <Trophy class="w-32 h-32 text-yellow-500" />
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border',
                                    category.gender?.toLowerCase() === 'male' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
                                ]">
                                    {{ category.gender }} - {{ category.age_category }}
                                </span>
                                <div class="h-px w-4 bg-slate-800"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    {{ category.format.replace('_', ' ') }}
                                </span>
                            </div>
                            <h3 class="text-3xl font-serif font-bold text-white group-hover:text-yellow-400 transition-colors italic">
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
                            class="w-full py-4 bg-white/5 hover:bg-yellow-500 hover:text-black rounded-2xl border border-white/10 hover:border-yellow-500 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
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
                        <Trophy :class="['w-8 h-8', selectedCategory.gender?.toLowerCase() === 'male' ? 'text-blue-500' : 'text-emerald-500']" />
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500">Tournament Bracket</span>
                            <div class="w-1 h-1 rounded-full bg-slate-700"></div>
                            <span :class="['text-[10px] font-black uppercase tracking-[0.3em]', selectedCategory.gender?.toLowerCase() === 'male' ? 'text-blue-500' : 'text-emerald-500']">
                                {{ selectedCategory.gender }}
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
                                    {{ roundLabel(selectedCategory.rounds, round.round, selectedCategory.entrant_count, 'single_elimination') }}
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
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg transition-all duration-300',
                                                    match.winner_id === match.player_one_id 
                                                        ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                                        : 'bg-blue-500/10 border border-blue-500/20 hover:bg-blue-500/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-blue-400']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg transition-all duration-300',
                                                    match.winner_id === match.player_two_id 
                                                        ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                                        : 'bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-emerald-400']">
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
                                {{ roundLabel(selectedCategory.rounds, selectedCategory.rounds, selectedCategory.entrant_count, 'single_elimination') }}
                            </h4>
                            <div v-for="match in grandFinalMatches(selectedCategory)" :key="match.id" class="w-80">
                                <div class="bg-linear-to-b from-yellow-500/10 to-transparent border-2 border-yellow-500/20 rounded-3xl p-6 shadow-[0_0_50px_rgba(234,179,8,0.1)]">
                                    <div class="flex items-center justify-center gap-4 mb-6">
                                        <div class="h-px w-8 bg-yellow-500/20"></div>
                                        <Trophy class="w-6 h-6 text-yellow-500" />
                                        <div class="h-px w-8 bg-yellow-500/20"></div>
                                    </div>
                                    <div class="space-y-4">
                                        <div :class="[
                                            'relative p-6 rounded-2xl transition-all duration-500 overflow-hidden group/player',
                                            match.winner_id === match.player_one_id 
                                                ? 'bg-yellow-500 shadow-[0_0_30px_rgba(234,179,8,0.3)]' 
                                                : 'bg-blue-500/10 border border-blue-500/20 hover:bg-blue-500/20'
                                        ]">
                                            <div class="flex items-center justify-between relative z-10">
                                                <div>
                                                    <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', match.winner_id === match.player_one_id ? 'text-black/50' : 'text-blue-500']">
                                                        Finalist
                                                    </div>
                                                    <div :class="['text-xl font-serif font-bold italic', match.winner_id === match.player_one_id ? 'text-black' : 'text-white']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </div>
                                                </div>
                                                <Trophy :class="['w-8 h-8 transition-transform duration-500 group-hover/player:scale-110', match.winner_id === match.player_one_id ? 'text-black' : 'text-blue-500/30']" />
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-4 py-2">
                                            <div class="h-px flex-1 bg-white/5"></div>
                                            <span class="text-[10px] font-black text-slate-600 uppercase tracking-[0.3em]">VS</span>
                                            <div class="h-px flex-1 bg-white/5"></div>
                                        </div>

                                        <div :class="[
                                            'relative p-6 rounded-2xl transition-all duration-500 overflow-hidden group/player',
                                            match.winner_id === match.player_two_id 
                                                ? 'bg-yellow-500 shadow-[0_0_30px_rgba(234,179,8,0.3)]' 
                                                : 'bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20'
                                        ]">
                                            <div class="flex items-center justify-between relative z-10">
                                                <div>
                                                    <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', match.winner_id === match.player_two_id ? 'text-black/50' : 'text-emerald-500']">
                                                        Finalist
                                                    </div>
                                                    <div :class="['text-xl font-serif font-bold italic', match.winner_id === match.player_two_id ? 'text-black' : 'text-white']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </div>
                                                </div>
                                                <Trophy :class="['w-8 h-8 transition-transform duration-500 group-hover/player:scale-110', match.winner_id === match.player_two_id ? 'text-black' : 'text-emerald-500/30']" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="selectedCategory.champion" class="mt-8 p-4 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 text-center">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-yellow-500 mb-1">Champion</div>
                                        <div class="text-xl font-serif font-bold text-white italic">{{ selectedCategory.champion }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedCategory.bronze_match" class="flex flex-col gap-6">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-400 text-center mb-2">
                                Bronze Match
                            </h4>
                            <div class="w-80">
                                <div class="bg-linear-to-b from-orange-500/10 to-transparent border border-orange-500/30 rounded-3xl p-5">
                                    <div class="space-y-3">
                                        <div :class="[
                                            'p-4 rounded-2xl transition-all duration-300',
                                            selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_one_id
                                                ? 'bg-orange-500/20 border border-orange-500/40'
                                                : 'bg-blue-500/10 border border-blue-500/20'
                                        ]">
                                            <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_one_id ? 'text-orange-300' : 'text-blue-400']">
                                                Bronze Contender
                                            </div>
                                            <div :class="['text-lg font-serif font-bold italic', selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_one_id ? 'text-orange-200' : 'text-white']">
                                                {{ selectedCategory.bronze_match.player_one || 'TBD' }}
                                            </div>
                                        </div>
                                        <div :class="[
                                            'p-4 rounded-2xl transition-all duration-300',
                                            selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_two_id
                                                ? 'bg-orange-500/20 border border-orange-500/40'
                                                : 'bg-emerald-500/10 border border-emerald-500/20'
                                        ]">
                                            <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_two_id ? 'text-orange-300' : 'text-emerald-400']">
                                                Bronze Contender
                                            </div>
                                            <div :class="['text-lg font-serif font-bold italic', selectedCategory.bronze_match.winner_id === selectedCategory.bronze_match.player_two_id ? 'text-orange-200' : 'text-white']">
                                                {{ selectedCategory.bronze_match.player_two || 'TBD' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- West Side (Right) -->
                        <div class="flex gap-12">
                            <div v-for="round in [...westRounds(selectedCategory)].reverse()" :key="`west-${round.round}`" class="flex flex-col gap-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center mb-4">
                                    {{ roundLabel(selectedCategory.rounds, round.round, selectedCategory.entrant_count, 'single_elimination') }}
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
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg flex-row-reverse transition-all duration-300',
                                                    match.winner_id === match.player_one_id 
                                                        ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                                        : 'bg-blue-500/10 border border-blue-500/20 hover:bg-blue-500/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-blue-400']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-yellow-500 shrink-0" />
                                                </div>
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg flex-row-reverse transition-all duration-300',
                                                    match.winner_id === match.player_two_id 
                                                        ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                                        : 'bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-emerald-400']">
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
                            <div :class="[
                                'flex-1 text-right p-4 rounded-2xl transition-all duration-300',
                                match.winner_id === match.player_one_id 
                                    ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                    : 'bg-blue-500/10 border border-blue-500/20'
                            ]">
                                <div :class="['text-xs font-bold mb-1', match.winner_id === match.player_one_id ? 'text-yellow-500/60' : 'text-blue-500/60']">
                                    {{ match.player_one_club || 'Club' }}
                                </div>
                                <div :class="['text-lg font-serif font-bold italic', match.winner_id === match.player_one_id ? 'text-yellow-500' : 'text-blue-400']">
                                    {{ match.player_one || 'TBD' }}
                                </div>
                            </div>
                            <div class="shrink-0 flex flex-col items-center gap-2">
                                <div class="text-[10px] font-black text-slate-700 uppercase tracking-widest">M{{ match.match_number }}</div>
                                <div class="text-2xl font-serif font-black italic text-slate-800">VS</div>
                            </div>
                            <div :class="[
                                'flex-1 p-4 rounded-2xl transition-all duration-300',
                                match.winner_id === match.player_two_id 
                                    ? 'bg-yellow-500/20 border border-yellow-500/40' 
                                    : 'bg-emerald-500/10 border border-emerald-500/20'
                            ]">
                                <div :class="['text-xs font-bold mb-1', match.winner_id === match.player_two_id ? 'text-yellow-500/60' : 'text-emerald-500/60']">
                                    {{ match.player_two_club || 'Club' }}
                                </div>
                                <div :class="['text-lg font-serif font-bold italic', match.winner_id === match.player_two_id ? 'text-yellow-500' : 'text-emerald-400']">
                                    {{ match.player_two || 'TBD' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
