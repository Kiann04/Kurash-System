<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { Instagram, Facebook, Trophy, Users, LayoutGrid, X, ArrowLeft, Search, SlidersHorizontal } from 'lucide-vue-next'
import { ref, computed } from 'vue'

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
    awards?: {
        gold: string | null
        silver: string | null
        bronze: string[]
    }
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
        case 'open': return 'text-primary bg-primary/10 border-primary/20';
        case 'ongoing': return 'text-accent bg-accent/10 border-accent/20';
        case 'completed': return 'text-muted-foreground bg-muted border-border';
        default: return 'text-secondary bg-secondary/10 border-secondary/20';
    }
}
</script>

<template>
<Head :title="`${tournament.name} - Brackets`" />
<div class="min-h-screen bg-background text-foreground font-sans selection:bg-accent selection:text-accent-foreground" style="--background: hsl(222 47% 6%)">
    <!-- Navbar (Consistent across public pages) -->
    <header class="border-b border-border bg-background/95 backdrop-blur-sm relative z-50">
      <div class="max-w-360 mx-auto px-8 h-20 flex items-center justify-between">
        <a :href="route('public.home')" class="flex items-center gap-3">
          <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
          <div class="h-8 w-px bg-border"></div>
          <div class="text-xs font-bold text-foreground leading-tight tracking-wide">
            KURASH<br/>SPORTS<br/>FEDERATION
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-x-2 xl:gap-x-4 text-[10px] xl:text-xs font-bold tracking-wider uppercase h-full font-sans">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap',
                (item.route === 'public.brackets.index' || item.route === 'public.brackets.show') ? 'text-accent' : 'text-muted-foreground hover:text-foreground'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-out shadow-[0_0_10px] shadow-accent/50',
                  (item.route === 'public.brackets.index' || item.route === 'public.brackets.show') ? 'w-full' : 'w-0 group-hover:w-full'
                ]"
              ></span>
            </a>
            <a 
              v-else
              href="#" 
              class="relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap text-muted-foreground hover:text-foreground"
            >
              {{ item.name }}
              <span class="absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-out shadow-[0_0_10px] shadow-accent/50 w-0 group-hover:w-full"></span>
            </a>
          </template>
        </nav>

        <div class="flex items-center gap-5 shrink-0">
          <div class="flex items-center gap-4">
            <button class="text-muted-foreground hover:text-accent transition-all duration-300 transform hover:scale-110 active:scale-95">
              <Instagram class="w-5 h-5" />
            </button>
            <button class="text-muted-foreground hover:text-foreground transition-all duration-300 transform hover:scale-110 active:scale-95">
              <Facebook class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
      <div class="absolute bottom-0 left-0 w-full h-px bg-linear-to-r from-transparent via-accent/50 to-transparent"></div>
    </nav>

    <main class="max-w-screen-2xl mx-auto px-4 py-8 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-primary/20 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Header -->
        <div class="mb-12 relative z-10">
            <div class="flex flex-col items-center text-center gap-6 border-b border-border pb-12">
                <div class="max-w-4xl mx-auto flex flex-col items-center">
                    <a :href="route('public.brackets.index')" class="flex items-center gap-2 text-accent/60 hover:text-accent font-bold text-[10px] uppercase tracking-[0.2em] mb-6 transition-colors group w-fit">
                        <ArrowLeft class="w-3.5 h-3.5 transform group-hover:-translate-x-1 transition-transform" />
                        Back to Tournaments
                    </a>
                    <div class="flex items-center gap-3 mb-6 justify-center">
                        <div :class="['px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border', getStatusColor(tournament.status)]">
                            {{ tournament.status }}
                        </div>
                        <div class="h-px w-8 bg-border"></div>
                        <div class="text-muted-foreground font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                            <Trophy class="w-3 h-3 text-accent/50" />
                            Official Event
                        </div>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-serif font-bold text-foreground mb-6 leading-tight tracking-tight">{{ tournament.name }}</h1>
                    <p class="text-muted-foreground text-lg max-w-2xl leading-relaxed font-light">
                        Follow the elimination paths and results for all weight categories. Select a category to view the full bracket layout.
                    </p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12 bg-card/50 p-2 rounded-2xl border border-border/50 backdrop-blur-sm relative z-10">
            <!-- Tabs -->
            <div class="flex items-center bg-muted/50 rounded-xl p-1 gap-1 w-full md:w-auto overflow-x-auto">
                <button v-for="tab in genderTabs" :key="tab"
                    @click="activeGenderTab = tab"
                    :class="[
                        'px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all duration-300 whitespace-nowrap', 
                        activeGenderTab === tab ? 'bg-accent text-accent-foreground shadow-lg shadow-accent/20' : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                    ]">
                    {{ tab }}
                </button>
            </div>

            <!-- Search -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="relative group w-full md:w-64">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-accent transition-colors" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search categories..." 
                        class="w-full bg-muted/50 border border-border rounded-xl py-2.5 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-accent/50 focus:ring-1 focus:ring-accent/50 transition-all" 
                    />
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="space-y-24">
            <!-- Bracket Categories Grid -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl bg-accent/20 flex items-center justify-center border border-accent/30">
                        <LayoutGrid class="w-6 h-6 text-accent" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-foreground uppercase tracking-wider italic">Categories</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-accent/50 to-transparent"></div>
                </div>

                <div v-if="filteredCategories.length === 0" class="py-16 text-center border border-dashed border-border rounded-5xl">
                    <p class="text-muted-foreground italic text-lg">No categories found matching your criteria.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <div v-for="category in filteredCategories" :key="category.id" 
                         class="group relative bg-card/50 rounded-4xl border border-border/50 hover:border-accent/30 transition-all duration-500 p-8 flex flex-col gap-8 overflow-hidden">
                        
                        <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                            <Trophy class="w-32 h-32 text-accent" />
                        </div>

                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <span :class="[
                                    'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border',
                                    category.gender?.toLowerCase() === 'male' ? 'bg-secondary/10 text-secondary border-secondary/20' : 'bg-primary/10 text-primary border-primary/20'
                                ]">
                                    {{ category.gender }} - {{ category.age_category }}
                                </span>
                                <div class="h-px w-4 bg-border"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                                    {{ category.format.replace('_', ' ') }}
                                </span>
                            </div>
                            <h3 class="text-3xl font-serif font-bold text-foreground group-hover:text-accent transition-colors italic">
                                {{ category.weight_category }}
                            </h3>
                        </div>

                        <div class="grid grid-cols-2 gap-4 relative z-10">
                            <div class="p-4 rounded-2xl bg-muted/50 border border-border/50">
                                <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Matches</div>
                                <div class="text-xl font-serif font-bold text-foreground">{{ category.matches_count }}</div>
                            </div>
                            <div class="p-4 rounded-2xl bg-muted/50 border border-border/50">
                                <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Status</div>
                                <div class="text-xl font-serif font-bold text-foreground">{{ category.completed_matches }}/{{ category.matches_count }}</div>
                            </div>
                        </div>

                        <button 
                            @click="openBracket(category)"
                            class="w-full py-4 bg-muted hover:bg-accent hover:text-accent-foreground rounded-2xl border border-border hover:border-accent text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-300"
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
        <div class="absolute inset-0 bg-background/95 backdrop-blur-xl" @click="closeBracket"></div>
        
        <!-- Modal Content -->
        <div class="relative w-full max-w-[95vw] h-full max-h-[95vh] bg-card rounded-4xl border border-border shadow-2xl overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="p-8 border-b border-border flex items-center justify-between shrink-0 bg-muted/20">
                <div class="flex items-center gap-8">
                    <div class="w-16 h-16 rounded-2xl bg-muted/50 border border-border flex items-center justify-center">
                        <Trophy :class="['w-8 h-8', selectedCategory.gender?.toLowerCase() === 'male' ? 'text-secondary' : 'text-primary']" />
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Tournament Bracket</span>
                            <div class="w-1 h-1 rounded-full bg-muted-foreground"></div>
                            <span :class="['text-[10px] font-black uppercase tracking-[0.3em]', selectedCategory.gender?.toLowerCase() === 'male' ? 'text-secondary' : 'text-primary']">
                                {{ selectedCategory.gender }}
                            </span>
                        </div>
                        <h3 class="text-4xl font-serif font-bold text-foreground italic">
                            {{ selectedCategory.age_category }} {{ selectedCategory.weight_category }}
                        </h3>
                    </div>
                </div>
                <button @click="closeBracket" class="w-12 h-12 rounded-2xl bg-muted/50 border border-border flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-muted transition-all">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Modal Body (Bracket Board) -->
            <div class="flex-1 overflow-auto p-8 bg-background/50">
                <!-- Single Elimination View -->
                <div v-if="selectedCategory.format === 'single_elimination'" class="min-w-max">
                    <div class="flex gap-12 justify-center items-start py-12">
                        <!-- East Side (Left) -->
                        <div class="flex gap-12">
                            <div v-for="round in eastRounds(selectedCategory)" :key="`east-${round.round}`" class="flex flex-col gap-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-center mb-4">
                                    {{ roundLabel(selectedCategory.rounds ?? finalRoundNumber(selectedCategory), round.round, selectedCategory.entrant_count, 'single_elimination') }}
                                </h4>
                                <div class="flex flex-col justify-around flex-1" :style="roundColumnStyle(round.round)">
                                    <div v-for="match in round.matches" :key="match.id" class="w-72">
                                        <!-- Match Card -->
                                        <div class="bg-card/50 border border-border rounded-2xl p-4 hover:border-border transition-all">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-[8px] font-black text-muted-foreground uppercase tracking-widest">M{{ match.match_number }}</span>
                                                <span v-if="match.status === 'ongoing'" class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                            </div>
                                            <div class="space-y-2">
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg transition-all duration-300',
                                                    match.winner_id === match.player_one_id 
                                                        ? 'bg-accent/20 border border-accent/40' 
                                                        : 'bg-secondary/10 border border-secondary/20 hover:bg-secondary/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_one_id ? 'text-accent' : 'text-secondary']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-accent shrink-0" />
                                                </div>
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg transition-all duration-300',
                                                    match.winner_id === match.player_two_id 
                                                        ? 'bg-accent/20 border border-accent/40' 
                                                        : 'bg-primary/10 border border-primary/20 hover:bg-primary/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pr-2', match.winner_id === match.player_two_id ? 'text-accent' : 'text-primary']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 text-accent shrink-0" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grand Final (Center) -->
                        <div class="flex flex-col gap-8">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-accent text-center mb-4">
                                {{ roundLabel(selectedCategory.rounds ?? finalRoundNumber(selectedCategory), (selectedCategory.rounds ?? finalRoundNumber(selectedCategory)), selectedCategory.entrant_count, 'single_elimination') }}
                            </h4>
                            <div v-for="match in grandFinalMatches(selectedCategory)" :key="match.id" class="w-80">
                                <div class="bg-linear-to-b from-accent/10 to-transparent border-2 border-accent/20 rounded-3xl p-6 shadow-[0_0_50px_hsl(var(--accent)/0.1)]">
                                    <div class="flex items-center justify-center gap-4 mb-6">
                                        <div class="h-px w-8 bg-accent/20"></div>
                                        <Trophy class="w-6 h-6 text-accent" />
                                        <div class="h-px w-8 bg-accent/20"></div>
                                    </div>
                                    <div class="space-y-4">
                                        <div :class="[
                                            'relative p-6 rounded-2xl transition-all duration-500 overflow-hidden group/player',
                                            match.winner_id === match.player_one_id 
                                                ? 'bg-accent shadow-[0_0_30px_hsl(var(--accent)/0.3)]' 
                                                : 'bg-secondary/10 border border-secondary/20 hover:bg-secondary/20'
                                        ]">
                                            <div class="flex items-center justify-between relative z-10">
                                                <div>
                                                    <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', match.winner_id === match.player_one_id ? 'text-accent-foreground/50' : 'text-secondary']">
                                                        Finalist
                                                    </div>
                                                    <div :class="['text-xl font-serif font-bold italic', match.winner_id === match.player_one_id ? 'text-accent-foreground' : 'text-foreground']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </div>
                                                </div>
                                                <Trophy :class="['w-8 h-8 transition-transform duration-500 group-hover/player:scale-110', match.winner_id === match.player_one_id ? 'text-accent-foreground' : 'text-secondary/30']" />
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-4 py-2">
                                            <div class="h-px flex-1 bg-border/50"></div>
                                            <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">VS</span>
                                            <div class="h-px flex-1 bg-border/50"></div>
                                        </div>

                                        <div :class="[
                                            'relative p-6 rounded-2xl transition-all duration-500 overflow-hidden group/player',
                                            match.winner_id === match.player_two_id 
                                                ? 'bg-accent shadow-[0_0_30px_hsl(var(--accent)/0.3)]' 
                                                : 'bg-primary/10 border border-primary/20 hover:bg-primary/20'
                                        ]">
                                            <div class="flex items-center justify-between relative z-10">
                                                <div>
                                                    <div :class="['text-[10px] font-black uppercase tracking-widest mb-1', match.winner_id === match.player_two_id ? 'text-accent-foreground/50' : 'text-primary']">
                                                        Finalist
                                                    </div>
                                                    <div :class="['text-xl font-serif font-bold italic', match.winner_id === match.player_two_id ? 'text-accent-foreground' : 'text-foreground']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </div>
                                                </div>
                                                <Trophy :class="['w-8 h-8 transition-transform duration-500 group-hover/player:scale-110', match.winner_id === match.player_two_id ? 'text-accent-foreground' : 'text-primary/30']" />
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="selectedCategory.champion" class="mt-8 p-4 rounded-2xl bg-accent/10 border border-accent/20 text-center">
                                        <div class="text-[10px] font-black uppercase tracking-widest text-accent mb-1">Champion</div>
                                        <div class="text-xl font-serif font-bold text-foreground italic">{{ selectedCategory.champion }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedCategory.awards?.bronze?.length" class="flex flex-col gap-3 items-center">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-accent text-center">
                                Bronze Medalists
                            </h4>
                            <div class="flex gap-2 flex-wrap items-center justify-center">
                                <span v-for="name in selectedCategory.awards.bronze" :key="name" class="px-3 py-1 rounded-full bg-accent/10 border border-accent/30 text-accent text-xs font-bold">
                                    {{ name }}
                                </span>
                            </div>
                        </div>

                        <!-- West Side (Right) -->
                        <div class="flex gap-12">
                            <div v-for="round in [...westRounds(selectedCategory)].reverse()" :key="`west-${round.round}`" class="flex flex-col gap-8">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-muted-foreground text-center mb-4">
                                    {{ roundLabel(selectedCategory.rounds, round.round, selectedCategory.entrant_count, 'single_elimination') }}
                                </h4>
                                <div class="flex flex-col justify-around flex-1" :style="roundColumnStyle(round.round)">
                                    <div v-for="match in round.matches" :key="match.id" class="w-72">
                                        <!-- Match Card -->
                                        <div class="bg-card/50 border border-border rounded-2xl p-4 hover:border-border transition-all text-right">
                                            <div class="flex items-center justify-between mb-3 flex-row-reverse">
                                                <span class="text-[8px] font-black text-muted-foreground uppercase tracking-widest">M{{ match.match_number }}</span>
                                                <span v-if="match.status === 'ongoing'" class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                            </div>
                                            <div class="space-y-2">
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg flex-row-reverse transition-all duration-300',
                                                    match.winner_id === match.player_one_id 
                                                        ? 'bg-accent/20 border border-accent/40' 
                                                        : 'bg-secondary/10 border border-secondary/20 hover:bg-secondary/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_one_id ? 'text-accent' : 'text-secondary']">
                                                        {{ match.player_one || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_one_id" class="w-3 h-3 text-accent shrink-0" />
                                                </div>
                                                <div :class="[
                                                    'flex items-center justify-between p-2 rounded-lg flex-row-reverse transition-all duration-300',
                                                    match.winner_id === match.player_two_id 
                                                        ? 'bg-accent/20 border border-accent/40' 
                                                        : 'bg-primary/10 border border-primary/20 hover:bg-primary/20'
                                                ]">
                                                    <span :class="['text-xs font-bold truncate pl-2', match.winner_id === match.player_two_id ? 'text-accent' : 'text-primary']">
                                                        {{ match.player_two || 'TBD' }}
                                                    </span>
                                                    <Trophy v-if="match.winner_id === match.player_two_id" class="w-3 h-3 text-accent shrink-0" />
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
                        <div v-for="match in selectedCategory.matches" :key="match.id" class="bg-card/50 border border-border rounded-3xl p-6 flex items-center justify-between gap-8">
                            <div :class="[
                                'flex-1 text-right p-4 rounded-2xl transition-all duration-300',
                                match.winner_id === match.player_one_id 
                                    ? 'bg-accent/20 border border-accent/40' 
                                    : 'bg-secondary/10 border border-secondary/20'
                            ]">
                                <div :class="['text-xs font-bold mb-1', match.winner_id === match.player_one_id ? 'text-accent/60' : 'text-secondary/60']">
                                    {{ match.player_one_club || 'Club' }}
                                </div>
                                <div :class="['text-lg font-serif font-bold italic', match.winner_id === match.player_one_id ? 'text-accent' : 'text-secondary']">
                                    {{ match.player_one || 'TBD' }}
                                </div>
                            </div>
                            <div class="shrink-0 flex flex-col items-center gap-2">
                                <div class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">M{{ match.match_number }}</div>
                                <div class="text-2xl font-serif font-black italic text-foreground">VS</div>
                            </div>
                            <div :class="[
                                'flex-1 p-4 rounded-2xl transition-all duration-300',
                                match.winner_id === match.player_two_id 
                                    ? 'bg-accent/20 border border-accent/40' 
                                    : 'bg-primary/10 border border-primary/20'
                            ]">
                                <div :class="['text-xs font-bold mb-1', match.winner_id === match.player_two_id ? 'text-accent/60' : 'text-primary/60']">
                                    {{ match.player_two_club || 'Club' }}
                                </div>
                                <div :class="['text-lg font-serif font-bold italic', match.winner_id === match.player_two_id ? 'text-accent' : 'text-primary']">
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
    <footer class="bg-muted border-t-2 border-accent/50 py-16">
        <div class="max-w-360 mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <!-- Brand Section -->
                <div class="md:col-span-2 space-y-6">
                    <a :href="route('public.home')" class="flex items-center gap-3">
                        <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
                        <div class="h-8 w-px bg-foreground/20"></div>
                        <div class="text-xs font-bold text-foreground leading-tight tracking-wide uppercase">
                            KURASH<br/>SPORTS<br/>FEDERATION
                        </div>
                    </a>
                    <p class="text-muted-foreground text-sm max-w-sm leading-relaxed">
                        The Kurash Sports Federation is dedicated to the promotion, development, and management of Kurash sports globally, upholding the highest standards of integrity and competition.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-foreground font-bold uppercase tracking-widest text-xs mb-6">Quick Links</h4>
                    <ul class="space-y-4 text-sm text-muted-foreground">
                        <li><a :href="route('public.home')" class="hover:text-accent transition-colors">Home</a></li>
                        <li><a :href="route('public.rankings.index')" class="hover:text-accent transition-colors">Rankings</a></li>
                        <li><a :href="route('public.tournaments.index')" class="hover:text-accent transition-colors">Tournaments</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">News</a></li>
                    </ul>
                </div>

                <!-- Legal & Contact -->
                <div>
                    <h4 class="text-foreground font-bold uppercase tracking-widest text-xs mb-6">Support</h4>
                    <ul class="space-y-4 text-sm text-muted-foreground">
                        <li><a href="#" class="hover:text-accent transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Contact</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-8 border-t border-border flex flex-col md:flex-row justify-between items-center gap-6 text-muted-foreground text-xs">
                <div>&copy; {{ new Date().getFullYear() }} Kurash Sports Federation. All rights reserved.</div>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-foreground transition-colors">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="hover:text-foreground transition-colors">
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
