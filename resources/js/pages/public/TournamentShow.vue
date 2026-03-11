<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { Instagram, Facebook, Calendar, MapPin, ChevronLeft, Users, Scale, Flame, Clock, CheckCircle2, Swords } from 'lucide-vue-next'
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
<Head :title="`${props.tournament.name} | Kurash Federation`" />
<div class="min-h-screen bg-black text-foreground font-sans selection:bg-accent selection:text-accent-foreground">
    <!-- Navbar (Consistent across public pages) -->
    <header class="border-b border-border bg-black/95 backdrop-blur-sm relative z-50">
      <div class="max-w-360 mx-auto px-8 h-20 flex items-center justify-between">
        <a :href="route('public.home')" class="flex items-center gap-3">
          <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
          <div class="h-8 w-px bg-border"></div>
          <div class="text-xs font-bold text-foreground leading-tight tracking-wide">
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
                item.route === 'public.tournaments.index' ? 'text-accent' : 'text-muted-foreground hover:text-foreground'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-out shadow-[0_0_10px] shadow-accent/50',
                  item.route === 'public.tournaments.index' ? 'w-full' : 'w-0 group-hover:w-full'
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
    </header>

    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-secondary/10 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Back Button & Header -->
        <div class="mb-12">
            <a :href="route('public.tournaments.index')" class="inline-flex items-center gap-2 text-accent/60 hover:text-accent font-bold text-[10px] uppercase tracking-widest transition-colors mb-8 group">
                <ChevronLeft class="w-4 h-4 transition-transform group-hover:-translate-x-1" />
                Back to Tournaments
            </a>
            
            <div class="flex flex-col items-center text-center gap-6 border-b border-border pb-12">
                <div class="max-w-4xl mx-auto flex flex-col items-center">
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
                    <div class="flex flex-wrap justify-center gap-8 text-muted-foreground">
                        <div class="flex items-center gap-3 bg-muted/20 px-4 py-2 rounded-full border border-border">
                            <Calendar class="w-4 h-4 text-accent" />
                            <span class="text-sm font-medium tracking-wide">{{ tournament.tournament_date ?? 'TBD' }}</span>
                        </div>
                        <div class="flex items-center gap-3 bg-muted/20 px-4 py-2 rounded-full border border-border">
                            <MapPin class="w-4 h-4 text-accent" />
                            <span class="text-sm font-medium tracking-wide">International Venue</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tournament Navigation Tabs -->
        <div class="mb-12 flex justify-center">
            <div class="bg-muted/20 p-1.5 rounded-2xl inline-flex relative">
                <button 
                    @click="currentView = 'male'"
                    :class="[
                        'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all relative z-10 flex items-center gap-2',
                        currentView === 'male' ? 'bg-secondary text-secondary-foreground shadow-lg shadow-secondary/25' : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
                    ]"
                >
                    <Users class="w-4 h-4" />
                    Male Division
                </button>
                <button 
                    @click="currentView = 'female'"
                    :class="[
                        'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all relative z-10 flex items-center gap-2',
                        currentView === 'female' ? 'bg-primary text-primary-foreground shadow-lg shadow-primary/25' : 'text-muted-foreground hover:text-foreground hover:bg-background/50'
                    ]"
                >
                    <Users class="w-4 h-4" />
                    Female Division
                </button>
            </div>
        </div>

        <!-- Male View -->
        <div v-if="currentView === 'male'" class="space-y-24">
            <!-- Fight Card Section -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="w-12 h-12 rounded-2xl bg-secondary/20 flex items-center justify-center border border-secondary/30">
                        <Swords class="w-6 h-6 text-secondary" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-foreground uppercase tracking-wider italic">Fight Card</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-secondary/50 to-transparent"></div>
                </div>

                <div v-if="maleMatches.length === 0" class="py-16 text-center border border-dashed border-border rounded-5xl">
                    <p class="text-muted-foreground italic text-lg">No male bouts scheduled yet.</p>
                </div>
                
                <div v-else v-for="group in maleFightCardGroups" :key="`male-${group.name}`" class="space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-border to-transparent"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-3">
                            <component :is="group.icon === 'Flame' ? Flame : (group.icon === 'Clock' ? Clock : CheckCircle2)" 
                                       :class="['w-4 h-4', group.icon === 'Flame' ? 'text-accent animate-pulse' : 'text-muted-foreground']" />
                            {{ group.name }}
                        </h3>
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-border to-transparent"></div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="match in group.matches" :key="match.id" 
                             class="group bg-card rounded-2xl border border-border hover:border-secondary/30 transition-all duration-300 overflow-hidden hover:shadow-lg hover:shadow-secondary/10">
                            <div class="flex flex-col md:flex-row items-stretch">
                                <!-- Match Info Sidebar -->
                                <div class="w-full md:w-32 bg-muted/50 border-b md:border-b-0 md:border-r border-border p-4 flex flex-row md:flex-col items-center md:items-start justify-between md:justify-center gap-2">
                                     <div class="flex items-center gap-2 md:flex-col md:items-start">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Match</span>
                                        <span class="text-xl font-serif font-bold text-foreground">{{ match.match_number }}</span>
                                     </div>
                                     <div class="flex items-center gap-2 md:flex-col md:items-start">
                                        <div v-if="match.status === 'ongoing'" class="px-2 py-1 rounded bg-accent/10 border border-accent/20 text-[10px] font-bold text-accent uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
                                            Live
                                        </div>
                                        <div v-else :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider', match.status === 'completed' ? 'bg-muted text-muted-foreground' : 'bg-secondary/10 text-secondary']">
                                            {{ match.status }}
                                        </div>
                                     </div>
                                </div>

                                <!-- Players Section -->
                                <div class="flex-1 p-6 flex flex-col md:flex-row items-center gap-8 md:gap-12">
                                    <!-- Player 1 -->
                                    <div class="flex-1 flex items-center justify-end gap-4 w-full">
                                        <div class="text-right">
                                            <h4 class="text-lg md:text-xl font-serif font-bold text-foreground group-hover:text-secondary transition-colors leading-tight">{{ match.player_one || 'TBD' }}</h4>
                                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mt-1">{{ match.player_one_club || 'Independent' }}</div>
                                        </div>
                                        <div class="relative shrink-0">
                                             <div class="w-12 h-12 md:w-16 md:h-16 rounded-full overflow-hidden border-2 border-border group-hover:border-secondary transition-colors bg-muted">
                                                <img :src="match.player_one_image ? `/storage/${match.player_one_image}` : '/images/default-profile.svg'" class="w-full h-full object-cover" />
                                             </div>
                                             <div v-if="match.winner_id === match.player_one_id" class="absolute -bottom-1 -right-1 w-5 h-5 md:w-6 md:h-6 rounded-full bg-accent flex items-center justify-center text-accent-foreground border-2 border-card">
                                                <Trophy class="w-3 h-3" />
                                             </div>
                                        </div>
                                    </div>

                                    <!-- VS -->
                                    <div class="shrink-0 flex flex-col items-center justify-center gap-1">
                                         <span class="text-2xl font-serif font-black italic text-muted-foreground/50">VS</span>
                                         <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{{ match.category_name }}</span>
                                    </div>

                                    <!-- Player 2 -->
                                    <div class="flex-1 flex flex-row-reverse md:flex-row items-center justify-end md:justify-start gap-4 w-full">
                                        <div class="relative shrink-0">
                                             <div class="w-12 h-12 md:w-16 md:h-16 rounded-full overflow-hidden border-2 border-border group-hover:border-secondary transition-colors bg-muted">
                                                <img :src="match.player_two_image ? `/storage/${match.player_two_image}` : '/images/default-profile.svg'" class="w-full h-full object-cover" />
                                             </div>
                                             <div v-if="match.winner_id === match.player_two_id" class="absolute -bottom-1 -left-1 w-5 h-5 md:w-6 md:h-6 rounded-full bg-accent flex items-center justify-center text-accent-foreground border-2 border-card">
                                                <Trophy class="w-3 h-3" />
                                             </div>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="text-lg md:text-xl font-serif font-bold text-foreground group-hover:text-secondary transition-colors leading-tight">{{ match.player_two || 'TBD' }}</h4>
                                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mt-1">{{ match.player_two_club || 'Independent' }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Result Sidebar (if completed) -->
                                <div v-if="match.status === 'completed'" class="w-full md:w-auto border-t md:border-t-0 md:border-l border-border p-4 flex items-center justify-center bg-muted/50">
                                     <div class="text-center md:w-24">
                                         <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Winner</div>
                                         <div class="text-sm font-bold text-accent wrap-break-word">{{ match.winner || 'Draw' }}</div>
                                     </div>
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
                    <div class="w-12 h-12 rounded-2xl bg-primary/20 flex items-center justify-center border border-primary/30">
                        <Swords class="w-6 h-6 text-primary" />
                    </div>
                    <h2 class="text-4xl font-serif font-bold text-foreground uppercase tracking-wider italic">Fight Card</h2>
                    <div class="h-px flex-1 bg-linear-to-r from-primary/50 to-transparent"></div>
                </div>

                <div v-if="femaleMatches.length === 0" class="py-16 text-center border border-dashed border-border rounded-5xl">
                    <p class="text-muted-foreground italic text-lg">No female bouts scheduled yet.</p>
                </div>
                
                <div v-else v-for="group in femaleFightCardGroups" :key="`female-${group.name}`" class="space-y-8">
                    <div class="flex items-center gap-4">
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-border to-transparent"></div>
                        <h3 class="text-xs font-black uppercase tracking-[0.3em] text-muted-foreground flex items-center gap-3">
                            <component :is="group.icon === 'Flame' ? Flame : (group.icon === 'Clock' ? Clock : CheckCircle2)" 
                                       :class="['w-4 h-4', group.icon === 'Flame' ? 'text-accent animate-pulse' : 'text-muted-foreground']" />
                            {{ group.name }}
                        </h3>
                        <div class="h-px flex-1 bg-linear-to-r from-transparent via-border to-transparent"></div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="match in group.matches" :key="match.id" 
                             class="group bg-card rounded-2xl border border-border hover:border-primary/30 transition-all duration-300 overflow-hidden hover:shadow-lg hover:shadow-primary/10">
                            <div class="flex flex-col md:flex-row items-stretch">
                                <!-- Match Info Sidebar -->
                                <div class="w-full md:w-32 bg-muted/50 border-b md:border-b-0 md:border-r border-border p-4 flex flex-row md:flex-col items-center md:items-start justify-between md:justify-center gap-2">
                                     <div class="flex items-center gap-2 md:flex-col md:items-start">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Match</span>
                                        <span class="text-xl font-serif font-bold text-foreground">{{ match.match_number }}</span>
                                     </div>
                                     <div class="flex items-center gap-2 md:flex-col md:items-start">
                                        <div v-if="match.status === 'ongoing'" class="px-2 py-1 rounded bg-accent/10 border border-accent/20 text-[10px] font-bold text-accent uppercase tracking-wider flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
                                            Live
                                        </div>
                                        <div v-else :class="['px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider', match.status === 'completed' ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary']">
                                            {{ match.status }}
                                        </div>
                                     </div>
                                </div>

                                <!-- Players Section -->
                                <div class="flex-1 p-6 flex flex-col md:flex-row items-center gap-8 md:gap-12">
                                    <!-- Player 1 -->
                                    <div class="flex-1 flex items-center justify-end gap-4 w-full">
                                        <div class="text-right">
                                            <h4 class="text-lg md:text-xl font-serif font-bold text-foreground group-hover:text-primary transition-colors leading-tight">{{ match.player_one || 'TBD' }}</h4>
                                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mt-1">{{ match.player_one_club || 'Independent' }}</div>
                                        </div>
                                        <div class="relative shrink-0">
                                             <div class="w-12 h-12 md:w-16 md:h-16 rounded-full overflow-hidden border-2 border-border group-hover:border-primary transition-colors bg-muted">
                                                <img :src="match.player_one_image ? `/storage/${match.player_one_image}` : '/images/default-profile.svg'" class="w-full h-full object-cover" />
                                             </div>
                                             <div v-if="match.winner_id === match.player_one_id" class="absolute -bottom-1 -right-1 w-5 h-5 md:w-6 md:h-6 rounded-full bg-accent flex items-center justify-center text-accent-foreground border-2 border-card">
                                                <Trophy class="w-3 h-3" />
                                             </div>
                                        </div>
                                    </div>

                                    <!-- VS -->
                                    <div class="shrink-0 flex flex-col items-center justify-center gap-1">
                                         <span class="text-2xl font-serif font-black italic text-muted-foreground/50">VS</span>
                                         <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{{ match.category_name }}</span>
                                    </div>

                                    <!-- Player 2 -->
                                    <div class="flex-1 flex flex-row-reverse md:flex-row items-center justify-end md:justify-start gap-4 w-full">
                                        <div class="relative shrink-0">
                                             <div class="w-12 h-12 md:w-16 md:h-16 rounded-full overflow-hidden border-2 border-border group-hover:border-primary transition-colors bg-muted">
                                                <img :src="match.player_two_image ? `/storage/${match.player_two_image}` : '/images/default-profile.svg'" class="w-full h-full object-cover" />
                                             </div>
                                             <div v-if="match.winner_id === match.player_two_id" class="absolute -bottom-1 -left-1 w-5 h-5 md:w-6 md:h-6 rounded-full bg-accent flex items-center justify-center text-accent-foreground border-2 border-card">
                                                <Trophy class="w-3 h-3" />
                                             </div>
                                        </div>
                                        <div class="text-left">
                                            <h4 class="text-lg md:text-xl font-serif font-bold text-foreground group-hover:text-primary transition-colors leading-tight">{{ match.player_two || 'TBD' }}</h4>
                                            <div class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground mt-1">{{ match.player_two_club || 'Independent' }}</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Result Sidebar (if completed) -->
                                <div v-if="match.status === 'completed'" class="w-full md:w-auto border-t md:border-t-0 md:border-l border-border p-4 flex items-center justify-center bg-muted/50">
                                     <div class="text-center md:w-24">
                                         <div class="text-[10px] font-black uppercase tracking-widest text-muted-foreground mb-1">Winner</div>
                                         <div class="text-sm font-bold text-accent wrap-break-word">{{ match.winner || 'Draw' }}</div>
                                     </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="categories.length === 0" class="py-24 text-center border border-dashed border-border rounded-5xl">
            <p class="text-muted-foreground italic text-xl mb-4">No published categories yet for this tournament.</p>
            <a :href="route('public.tournaments.index')" class="text-accent font-bold text-xs uppercase tracking-widest hover:underline">
                Check other tournaments
            </a>
        </div>
    </main>

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
