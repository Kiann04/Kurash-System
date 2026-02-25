<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
const route = window.route;
import { ref, computed } from 'vue'
import { Instagram, Facebook, Calendar, MapPin, Trophy, ChevronLeft, Users, Scale, LayoutGrid, CheckCircle2, X, Maximize2, Minimize2 } from 'lucide-vue-next'

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
    status: 'scheduled' | 'completed'
    player_one_id: number | null
    player_one: string | null
    player_two_id: number | null
    player_two: string | null
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

const selectedCategory = ref<Category | null>(null)
const isModalOpen = ref(false)
const fullscreenBracketId = ref<number | null>(null)

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
    { name: 'Anti-doping' },
    { name: 'Tournaments', route: 'public.tournaments.index' },
    { name: 'Rankings', route: 'public.rankings.index' },
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

        <nav class="hidden lg:flex items-center gap-2 xl:gap-4 text-[10px] xl:text-xs font-bold tracking-widest uppercase h-full">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-4 transition-all duration-300 group whitespace-nowrap',
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

        <!-- Categories Section -->
        <div>
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-serif font-bold text-white">Competition Categories</h2>
                <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 px-4 py-2 bg-white/5 rounded-full border border-white/5">
                    {{ categories.length }} Categories Listed
                </div>
            </div>

            <!-- Stylized Grid for Categories -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="category in categories" :key="category.id" 
                     class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 p-8 hover:border-yellow-500/30 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.05)]">
                    
                    <div class="flex justify-between items-start mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-yellow-500/10 flex items-center justify-center border border-yellow-500/20 group-hover:bg-yellow-500 group-hover:text-black transition-all duration-500">
                                <Users class="w-5 h-5" />
                            </div>
                            <div>
                                <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-0.5">{{ category.gender }}</div>
                                <div class="text-lg font-bold text-white">{{ category.age_category }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 mb-8">
                        <div class="flex items-center justify-between py-3 border-b border-slate-800/50">
                            <div class="flex items-center gap-3 text-slate-400 text-xs font-bold uppercase tracking-widest">
                                <Scale class="w-4 h-4 text-yellow-500/30" />
                                Weight
                            </div>
                            <div class="text-white font-bold">{{ category.weight_category }}</div>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-800/50">
                            <div class="flex items-center gap-3 text-slate-400 text-xs font-bold uppercase tracking-widest">
                                <LayoutGrid class="w-4 h-4 text-yellow-500/30" />
                                Format
                            </div>
                            <div class="text-white font-bold capitalize">{{ category.format.replace('_', ' ') }}</div>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3 text-slate-400 text-xs font-bold uppercase tracking-widest">
                                <CheckCircle2 class="w-4 h-4 text-yellow-500/30" />
                                Progress
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold">{{ category.completed_matches }} / {{ category.matches_count }}</span>
                                <span class="text-slate-500 text-[10px] uppercase font-black">Matches</span>
                            </div>
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

            <!-- Bracket Modal -->
            <div v-if="isModalOpen && selectedCategory" class="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-8">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-[#050a14]/95 backdrop-blur-xl" @click="closeBracket"></div>
                
                <!-- Modal Content -->
                <div class="relative w-full max-w-7xl h-full max-h-[90vh] bg-[#0f172a] rounded-[40px] border border-white/10 shadow-2xl overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div class="p-8 border-b border-white/10 flex items-center justify-between shrink-0">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 rounded-full bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 text-[10px] font-black uppercase tracking-widest">
                                    {{ selectedCategory.gender }}
                                </span>
                                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                                    {{ selectedCategory.format.replace('_', ' ') }}
                                </span>
                            </div>
                            <h3 class="text-3xl font-serif font-bold text-white">
                                {{ selectedCategory.age_category }} {{ selectedCategory.weight_category }}
                            </h3>
                        </div>
                        <button 
                            @click="closeBracket"
                            class="p-4 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-all border border-white/10"
                        >
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
                                            {{ roundLabel(finalRoundNumber(selectedCategory), round.round, 'East') }}
                                        </h4>
                                        <div class="flex flex-col h-full justify-around" :style="roundColumnStyle(round.round)">
                                            <div v-for="match in round.matches" :key="match.id" class="w-64">
                                                <div class="bg-[#0f172a] rounded-2xl border border-white/10 overflow-hidden shadow-lg">
                                                    <div class="p-2 border-b border-white/5 flex justify-between items-center bg-white/2">
                                                        <span class="text-[8px] font-black uppercase text-slate-500 tracking-widest">M{{ match.match_number }}</span>
                                                        <span :class="['text-[8px] font-black uppercase tracking-widest', match.status === 'completed' ? 'text-green-500' : 'text-yellow-500/50']">
                                                            {{ match.status }}
                                                        </span>
                                                    </div>
                                                    <div class="p-3 space-y-2">
                                                        <div :class="['flex justify-between items-center p-2 rounded-lg transition-colors', match.winner_id === match.player_one_id ? 'bg-yellow-500/10 text-yellow-500' : 'text-slate-400']">
                                                            <span class="text-xs font-bold truncate pr-2">{{ match.player_one || 'BYE' }}</span>
                                                            <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 shrink-0" />
                                                        </div>
                                                        <div :class="['flex justify-between items-center p-2 rounded-lg transition-colors', match.winner_id === match.player_two_id ? 'bg-yellow-500/10 text-yellow-500' : 'text-slate-400']">
                                                            <span class="text-xs font-bold truncate pr-2">{{ match.player_two || 'BYE' }}</span>
                                                            <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 shrink-0" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Center (Grand Final) -->
                                <div class="flex flex-col gap-8">
                                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-yellow-500 text-center mb-4">
                                        {{ roundLabel(finalRoundNumber(selectedCategory), finalRoundNumber(selectedCategory)) }}
                                    </h4>
                                    <div class="flex flex-col h-full justify-center">
                                        <div v-for="match in grandFinalMatches(selectedCategory)" :key="match.id" class="w-72">
                                            <div class="bg-[#0f172a] rounded-[32px] border-2 border-yellow-500/30 overflow-hidden shadow-[0_0_50px_rgba(234,179,8,0.1)]">
                                                <div class="p-4 border-b border-yellow-500/10 flex justify-between items-center bg-yellow-500/5">
                                                    <span class="text-[10px] font-black uppercase text-yellow-500 tracking-[0.2em]">Championship</span>
                                                    <Trophy class="w-4 h-4 text-yellow-500" />
                                                </div>
                                                <div class="p-6 space-y-4">
                                                    <div :class="['flex justify-between items-center p-4 rounded-2xl transition-all', match.winner_id === match.player_one_id ? 'bg-yellow-500 text-black shadow-lg shadow-yellow-500/20' : 'bg-white/5 text-white border border-white/5']">
                                                        <span class="text-sm font-black truncate pr-2 uppercase tracking-tight">{{ match.player_one || 'TBD' }}</span>
                                                        <CheckCircle2 v-if="match.winner_id === match.player_one_id" class="w-5 h-5 shrink-0" />
                                                    </div>
                                                    <div class="flex items-center gap-4">
                                                        <div class="h-px flex-1 bg-white/10"></div>
                                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">VS</span>
                                                        <div class="h-px flex-1 bg-white/10"></div>
                                                    </div>
                                                    <div :class="['flex justify-between items-center p-4 rounded-2xl transition-all', match.winner_id === match.player_two_id ? 'bg-yellow-500 text-black shadow-lg shadow-yellow-500/20' : 'bg-white/5 text-white border border-white/5']">
                                                        <span class="text-sm font-black truncate pr-2 uppercase tracking-tight">{{ match.player_two || 'TBD' }}</span>
                                                        <CheckCircle2 v-if="match.winner_id === match.player_two_id" class="w-5 h-5 shrink-0" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- West Side (Right) -->
                                <div class="flex gap-12">
                                    <div v-for="round in westRounds(selectedCategory)" :key="`west-${round.round}`" class="flex flex-col gap-8">
                                        <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 text-center mb-4">
                                            {{ roundLabel(finalRoundNumber(selectedCategory), round.round, 'West') }}
                                        </h4>
                                        <div class="flex flex-col h-full justify-around" :style="roundColumnStyle(round.round)">
                                            <div v-for="match in round.matches" :key="match.id" class="w-64">
                                                <div class="bg-[#0f172a] rounded-2xl border border-white/10 overflow-hidden shadow-lg">
                                                    <div class="p-2 border-b border-white/5 flex justify-between items-center bg-white/2">
                                                        <span class="text-[8px] font-black uppercase text-slate-500 tracking-widest">M{{ match.match_number }}</span>
                                                        <span :class="['text-[8px] font-black uppercase tracking-widest', match.status === 'completed' ? 'text-green-500' : 'text-yellow-500/50']">
                                                            {{ match.status }}
                                                        </span>
                                                    </div>
                                                    <div class="p-3 space-y-2">
                                                        <div :class="['flex justify-between items-center p-2 rounded-lg transition-colors', match.winner_id === match.player_one_id ? 'bg-yellow-500/10 text-yellow-500' : 'text-slate-400']">
                                                            <span class="text-xs font-bold truncate pr-2">{{ match.player_one || 'BYE' }}</span>
                                                            <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 shrink-0" />
                                                        </div>
                                                        <div :class="['flex justify-between items-center p-2 rounded-lg transition-colors', match.winner_id === match.player_two_id ? 'bg-yellow-500/10 text-yellow-500' : 'text-slate-400']">
                                                            <span class="text-xs font-bold truncate pr-2">{{ match.player_two || 'BYE' }}</span>
                                                            <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 shrink-0" />
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
                        <div v-else class="space-y-12 py-12">
                            <div v-for="round in roundsForBracket(selectedCategory)" :key="round.round" class="space-y-6">
                                <h4 class="text-xs font-black uppercase tracking-[0.4em] text-yellow-500 text-center border-b border-white/5 pb-4">
                                    {{ roundLabel(finalRoundNumber(selectedCategory), round.round) }}
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    <div v-for="match in round.matches" :key="match.id" class="bg-[#0f172a] rounded-[32px] border border-white/10 overflow-hidden group hover:border-yellow-500/30 transition-all duration-500">
                                        <div class="p-4 border-b border-white/5 flex justify-between items-center bg-white/2">
                                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Match {{ match.match_number }}</span>
                                            <span :class="['text-[10px] font-black uppercase tracking-widest', match.status === 'completed' ? 'text-green-500' : 'text-yellow-500/50']">
                                                {{ match.status }}
                                            </span>
                                        </div>
                                        <div class="p-6 space-y-4">
                                            <div :class="['flex justify-between items-center p-3 rounded-2xl transition-all', match.winner_id === match.player_one_id ? 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20' : 'bg-white/5 text-slate-400 border border-white/5']">
                                                <span class="text-sm font-bold truncate pr-2">{{ match.player_one || 'BYE' }}</span>
                                                <Trophy v-if="match.winner_id === match.player_one_id" class="w-4 h-4 shrink-0" />
                                            </div>
                                            <div :class="['flex justify-between items-center p-3 rounded-2xl transition-all', match.winner_id === match.player_two_id ? 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20' : 'bg-white/5 text-slate-400 border border-white/5']">
                                                <span class="text-sm font-bold truncate pr-2">{{ match.player_two || 'BYE' }}</span>
                                                <Trophy v-if="match.winner_id === match.player_two_id" class="w-4 h-4 shrink-0" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 bg-[#050a14] border-t border-white/10 flex justify-between items-center">
                        <div class="flex gap-8">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.5)]"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Winner</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-white/10"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Scheduled</span>
                            </div>
                        </div>
                        <div v-if="selectedCategory.champion" class="flex items-center gap-3 px-6 py-2 bg-yellow-500/10 border border-yellow-500/20 rounded-full">
                            <Trophy class="w-4 h-4 text-yellow-500" />
                            <span class="text-xs font-black uppercase tracking-widest text-yellow-500">Champion: {{ selectedCategory.champion }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="categories.length === 0" class="py-24 text-center border border-dashed border-slate-800 rounded-[40px]">
                <p class="text-slate-500 italic text-xl mb-4">No published categories yet for this tournament.</p>
                <a :href="route('public.tournaments.index')" class="text-yellow-500 font-bold text-xs uppercase tracking-widest hover:underline">
                    Check other tournaments
                </a>
            </div>
        </div>
    </main>
</div>
</template>
