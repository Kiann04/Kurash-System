<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
const route = window.route;
import { 
    Trophy, 
    Users, 
    ChevronDown,
    Search,
    Check
} from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

interface Player {
    id: number
    name: string
    gender: string
    club: string
    profile_image: string | null
    points: number
}

const props = defineProps<{
    players: Player[]
}>()

const defaultProfileImage = '/images/default-profile.svg'

const search = ref('')
const selectedGender = ref('')
const selectedAcademy = ref('')
const maleLimit = ref(10)
const femaleLimit = ref(10)

const genders = ['male', 'female']
const academies = computed(() => {
    const clubs = props.players.map(p => p.club).filter(Boolean)
    return [...new Set(clubs)].sort()
})

const filteredPlayers = computed(() => {
    return props.players.filter(player => {
        const matchesSearch = player.name.toLowerCase().includes(search.value.toLowerCase()) ||
                            player.club.toLowerCase().includes(search.value.toLowerCase())
        const matchesGender = !selectedGender.value || player.gender.toLowerCase() === selectedGender.value.toLowerCase()
        const matchesAcademy = !selectedAcademy.value || player.club === selectedAcademy.value
        return matchesSearch && matchesGender && matchesAcademy
    })
})

const maleRankings = computed(() => {
    return filteredPlayers.value
        .filter(p => p.gender.toLowerCase() === 'male')
        .sort((a, b) => b.points - a.points)
        .map((p, index) => ({ ...p, rank: index + 1 }))
})

const femaleRankings = computed(() => {
    return filteredPlayers.value
        .filter(p => p.gender.toLowerCase() === 'female')
        .sort((a, b) => b.points - a.points)
        .map((p, index) => ({ ...p, rank: index + 1 }))
})

const visibleMaleRankings = computed(() => maleRankings.value.slice(0, maleLimit.value))
const visibleFemaleRankings = computed(() => femaleRankings.value.slice(0, femaleLimit.value))

const getRankColor = (rank: number) => {
    if (rank === 1) return 'text-accent'
    if (rank === 2) return 'text-muted-foreground'
    if (rank === 3) return 'text-accent/70'
    return 'text-muted-foreground/70'
}

const getRankBorder = (rank: number) => {
    if (rank === 1) return 'border-accent'
    if (rank === 2) return 'border-muted-foreground'
    if (rank === 3) return 'border-accent/70'
    return 'border-border'
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
</script>

<template>
    <Head title="Rankings | Kurash Federation" />
    <div class="min-h-screen bg-background text-foreground font-sans selection:bg-accent selection:text-accent-foreground">
        <!-- Navbar -->
        <header class="border-b border-border bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/60 relative z-50">
            <div class="max-w-360 mx-auto px-8 h-20 flex items-center justify-between">
                <a :href="route('public.home')" class="flex items-center gap-3">
                    <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
                    <div class="h-8 w-px bg-border"></div>
                    <div class="text-xs font-bold text-foreground leading-tight tracking-wide">
                        KURASH<br/>SPORTS<br/>FEDERATION
                    </div>
                </a>

                <nav class="hidden lg:flex items-center gap-x-2 xl:gap-x-4 text-xs xl:text-xs font-bold tracking-wider uppercase h-full font-sans">
                    <template v-for="item in navItems" :key="item.name">
                        <a 
                            v-if="item.route"
                            :href="route(item.route)"
                            :class="[
                                'relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap',
                                item.route === 'public.rankings.index' ? 'text-accent' : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            {{ item.name }}
                            <span 
                                :class="[
                                    'absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-out shadow-[0_0_10px] shadow-accent/50',
                                    item.route === 'public.rankings.index' ? 'w-full' : 'w-0 group-hover:w-full'
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
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg>
                        </button>
                        <button class="text-muted-foreground hover:text-foreground transition-all duration-300 transform hover:scale-110 active:scale-95">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="absolute bottom-0 left-0 w-full h-px bg-linear-to-r from-transparent via-accent/50 to-transparent"></div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-12 relative">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-primary/5 blur-3xl rounded-full pointer-events-none"></div>

            <section class="mb-24">
                <div class="text-center mb-12">
                    <h2 class="text-5xl font-sophisticated font-bold text-accent mb-4 uppercase tracking-wider">Rankings</h2>
                    <p class="text-muted-foreground text-lg">Official Federation player standings</p>
                </div>

                <!-- Filter Bar -->
                <div class="bg-card/50 backdrop-blur-md border border-border rounded-2xl p-8 shadow-2xl mb-16 max-w-5xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="relative group">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search athlete or club..." 
                                class="w-full bg-background border border-input text-foreground rounded-lg pl-10 pr-4 py-3 focus:outline-none focus:border-accent transition-colors text-sm placeholder:text-muted-foreground"
                            />
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-accent transition-colors" />
                        </div>

                        <!-- Gender -->
                        <div class="relative group/select">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" class="w-full justify-between bg-background border-input text-foreground hover:bg-muted hover:text-foreground h-11.5 px-4 py-3 font-normal text-sm">
                                        {{ selectedGender ? (selectedGender.charAt(0).toUpperCase() + selectedGender.slice(1)) : 'All Genders' }}
                                        <ChevronDown class="ml-2 h-4 w-4 text-muted-foreground group-hover/select:text-accent transition-colors" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) bg-background border-border text-foreground">
                                    <DropdownMenuItem @click="selectedGender = ''" class="cursor-pointer hover:bg-muted hover:text-foreground">
                                        All Genders
                                        <Check v-if="selectedGender === ''" class="ml-auto h-4 w-4" />
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-for="gender in genders" :key="gender" @click="selectedGender = gender" class="cursor-pointer hover:bg-muted hover:text-foreground">
                                        {{ gender.charAt(0).toUpperCase() + gender.slice(1) }}
                                        <Check v-if="selectedGender === gender" class="ml-auto h-4 w-4" />
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <!-- Club -->
                        <div class="relative group/select">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" class="w-full justify-between bg-background border-input text-foreground hover:bg-muted hover:text-foreground h-11.5 px-4 py-3 font-normal text-sm">
                                        <span class="truncate">{{ selectedAcademy || 'All Clubs' }}</span>
                                        <ChevronDown class="ml-2 h-4 w-4 text-muted-foreground group-hover/select:text-accent transition-colors" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) bg-background border-border text-foreground max-h-75 overflow-y-auto">
                                    <DropdownMenuItem @click="selectedAcademy = ''" class="cursor-pointer hover:bg-muted hover:text-foreground">
                                        All Clubs
                                        <Check v-if="selectedAcademy === ''" class="ml-auto h-4 w-4" />
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-for="academy in academies" :key="academy" @click="selectedAcademy = academy" class="cursor-pointer hover:bg-muted hover:text-foreground">
                                        {{ academy }}
                                        <Check v-if="selectedAcademy === academy" class="ml-auto h-4 w-4" />
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>

                <!-- Rankings List (Two Columns) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Male Column (Blue -> Secondary) -->
                    <div class="bg-card/30 rounded-3xl overflow-hidden border border-border/50">
                        <div class="bg-secondary p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-secondary-foreground uppercase tracking-wider">Male Rankings</h3>
                                <div class="flex items-center gap-2 text-secondary-foreground/80 text-xs mt-1">
                                    <Users class="w-3 h-3" />
                                    <span>{{ maleRankings.length }} Athletes</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <div v-for="athlete in visibleMaleRankings" :key="athlete.id" 
                                 class="flex items-center gap-6 p-4 rounded-3xl bg-card hover:bg-secondary/10 transition-all group cursor-pointer border border-border hover:border-secondary/30 shadow-lg relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-8 text-8xl font-black text-foreground/5 italic select-none group-hover:text-secondary/10 transition-colors">
                                    {{ athlete.rank }}
                                </div>

                                <div class="flex flex-col items-center justify-center w-14 shrink-0 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl border-2 flex items-center justify-center text-xl font-black italic transition-all group-hover:scale-110 group-hover:rotate-3 shadow-inner"
                                         :class="[getRankBorder(athlete.rank), getRankColor(athlete.rank), athlete.rank <= 3 ? 'bg-muted' : 'bg-transparent']">
                                        {{ athlete.rank }}
                                    </div>
                                    <div v-if="athlete.rank <= 3" class="absolute -top-2 -right-2">
                                        <Trophy class="w-6 h-6 drop-shadow-[0_0_8px_rgba(255,255,255,0.3)] animate-pulse"
                                                :class="getRankColor(athlete.rank)" />
                                    </div>
                                </div>

                                <div class="relative shrink-0 z-10">
                                    <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-border group-hover:border-secondary transition-all duration-500 shadow-2xl bg-muted group-hover:shadow-secondary/20">
                                        <img :src="athlete.profile_image ? `/storage/${athlete.profile_image}` : defaultProfileImage" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 z-10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-secondary/10 text-secondary border border-secondary/20">Active</span>
                                        <div class="h-px w-8 bg-border"></div>
                                    </div>
                                    <h4 class="text-2xl font-black text-foreground truncate group-hover:text-secondary transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-2 h-2 rounded-full bg-accent"></div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.club }}</div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 px-4 z-10">
                                    <div class="flex flex-col items-end">
                                        <div class="text-3xl font-black text-foreground tracking-tighter group-hover:text-secondary transition-colors">{{ athlete.points }}</div>
                                        <div class="text-xs text-muted-foreground uppercase tracking-widest font-black bg-muted px-2 py-0.5 rounded mt-1">PTS</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Show More Button -->
                            <div v-if="maleRankings.length > maleLimit" class="pt-4 flex justify-center">
                                <button 
                                    @click="maleLimit += 10"
                                    class="px-8 py-3 bg-secondary/20 hover:bg-secondary text-secondary hover:text-secondary-foreground rounded-xl border border-secondary/30 hover:border-secondary transition-all duration-300 text-xs font-black uppercase tracking-widest"
                                >
                                    Show More
                                </button>
                            </div>

                            <div v-if="maleRankings.length === 0" class="p-8 text-center text-muted-foreground italic">No male athletes found</div>
                        </div>
                    </div>

                    <!-- Female Column (Green -> Primary) -->
                    <div class="bg-card/30 rounded-3xl overflow-hidden border border-border/50">
                        <div class="bg-primary p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-primary-foreground uppercase tracking-wider">Female Rankings</h3>
                                <div class="flex items-center gap-2 text-primary-foreground/80 text-xs mt-1">
                                    <Users class="w-3 h-3" />
                                    <span>{{ femaleRankings.length }} Athletes</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <div v-for="athlete in visibleFemaleRankings" :key="athlete.id" 
                                 class="flex items-center gap-6 p-4 rounded-3xl bg-card hover:bg-primary/10 transition-all group cursor-pointer border border-border hover:border-primary/30 shadow-lg relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-8 text-8xl font-black text-foreground/5 italic select-none group-hover:text-primary/10 transition-colors">
                                    {{ athlete.rank }}
                                </div>

                                <div class="flex flex-col items-center justify-center w-14 shrink-0 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl border-2 flex items-center justify-center text-xl font-black italic transition-all group-hover:scale-110 group-hover:rotate-3 shadow-inner"
                                         :class="[getRankBorder(athlete.rank), getRankColor(athlete.rank), athlete.rank <= 3 ? 'bg-muted' : 'bg-transparent']">
                                        {{ athlete.rank }}
                                    </div>
                                    <div v-if="athlete.rank <= 3" class="absolute -top-2 -right-2">
                                        <Trophy class="w-6 h-6 drop-shadow-[0_0_8px_rgba(255,255,255,0.3)] animate-pulse"
                                                :class="getRankColor(athlete.rank)" />
                                    </div>
                                </div>

                                <div class="relative shrink-0 z-10">
                                    <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-border group-hover:border-primary transition-all duration-500 shadow-2xl bg-muted group-hover:shadow-primary/20">
                                        <img :src="athlete.profile_image ? `/storage/${athlete.profile_image}` : defaultProfileImage" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 z-10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-primary/10 text-primary border border-primary/20">Active</span>
                                        <div class="h-px w-8 bg-border"></div>
                                    </div>
                                    <h4 class="text-2xl font-black text-foreground truncate group-hover:text-primary transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-2 h-2 rounded-full bg-accent"></div>
                                        <div class="text-muted-foreground text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.club }}</div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 px-4 z-10">
                                    <div class="flex flex-col items-end">
                                        <div class="text-3xl font-black text-foreground tracking-tighter group-hover:text-primary transition-colors">{{ athlete.points }}</div>
                                        <div class="text-xs text-muted-foreground uppercase tracking-widest font-black bg-muted px-2 py-0.5 rounded mt-1">PTS</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Show More Button -->
                            <div v-if="femaleRankings.length > femaleLimit" class="pt-4 flex justify-center">
                                <button 
                                    @click="femaleLimit += 10"
                                    class="px-8 py-3 bg-primary/20 hover:bg-primary text-primary hover:text-primary-foreground rounded-xl border border-primary/30 hover:border-primary transition-all duration-300 text-xs font-black uppercase tracking-widest"
                                >
                                    Show More
                                </button>
                            </div>

                            <div v-if="femaleRankings.length === 0" class="p-8 text-center text-muted-foreground italic">No female athletes found</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-card border-t-2 border-accent/50 py-16">
            <div class="max-w-360 mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <!-- Brand Section -->
                    <div class="md:col-span-2 space-y-6">
                        <a :href="route('public.home')" class="flex items-center gap-3">
                            <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
                            <div class="h-8 w-px bg-border"></div>
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
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.163 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
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
