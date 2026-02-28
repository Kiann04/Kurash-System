<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
const route = window.route;
import { ref, computed } from 'vue'
import { 
    Instagram, 
    Facebook, 
    Trophy, 
    Users, 
    Filter, 
    ChevronDown,
    Search
} from 'lucide-vue-next'

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
    if (rank === 1) return 'text-yellow-400'
    if (rank === 2) return 'text-gray-300'
    if (rank === 3) return 'text-amber-600'
    return 'text-slate-500'
}

const getRankBorder = (rank: number) => {
    if (rank === 1) return 'border-yellow-400'
    if (rank === 2) return 'border-gray-300'
    if (rank === 3) return 'border-amber-600'
    return 'border-slate-700'
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
                                item.route === 'public.rankings.index' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
                            ]"
                        >
                            {{ item.name }}
                            <span 
                                :class="[
                                    'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                                    item.route === 'public.rankings.index' ? 'w-full' : 'w-0 group-hover:w-full'
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
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[100px] rounded-full pointer-events-none"></div>

            <section class="mb-24">
                <div class="text-center mb-12">
                    <h2 class="text-5xl font-sophisticated font-bold text-yellow-500 mb-4 uppercase tracking-wider">Rankings</h2>
                    <p class="text-slate-400 text-lg">Official Federation player standings</p>
                </div>

                <!-- Filter Bar -->
                <div class="bg-[#0f172a]/50 backdrop-blur-md border border-slate-800 rounded-2xl p-8 shadow-2xl mb-16 max-w-5xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="relative group">
                            <input 
                                v-model="search"
                                type="text" 
                                placeholder="Search athlete or club..." 
                                class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg pl-10 pr-4 py-3 focus:outline-none focus:border-yellow-500 transition-colors text-sm"
                            />
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-focus-within:text-yellow-500 transition-colors" />
                        </div>

                        <!-- Gender -->
                        <div class="relative group/select">
                            <select v-model="selectedGender" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                                <option value="">All Genders</option>
                                <option v-for="gender in genders" :key="gender" :value="gender">{{ gender.charAt(0).toUpperCase() + gender.slice(1) }}</option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
                        </div>

                        <!-- Club -->
                        <div class="relative group/select">
                            <select v-model="selectedAcademy" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                                <option value="">All Clubs</option>
                                <option v-for="academy in academies" :key="academy" :value="academy">{{ academy }}</option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Rankings List (Two Columns) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Male Column -->
                    <div class="bg-[#0f172a]/30 rounded-3xl overflow-hidden border border-slate-800/50">
                        <div class="bg-blue-600 p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-white uppercase tracking-wider">Male Rankings</h3>
                                <div class="flex items-center gap-2 text-blue-100 text-xs mt-1">
                                    <Users class="w-3 h-3" />
                                    <span>{{ maleRankings.length }} Athletes</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <div v-for="athlete in visibleMaleRankings" :key="athlete.id" 
                                 class="flex items-center gap-6 p-4 rounded-3xl bg-slate-900/50 hover:bg-blue-600/10 transition-all group cursor-pointer border border-slate-800 hover:border-blue-500/30 shadow-lg relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-8 text-8xl font-black text-white/5 italic select-none group-hover:text-blue-500/10 transition-colors">
                                    {{ athlete.rank }}
                                </div>

                                <div class="flex flex-col items-center justify-center w-14 shrink-0 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl border-2 flex items-center justify-center text-xl font-black italic transition-all group-hover:scale-110 group-hover:rotate-3 shadow-inner"
                                         :class="[getRankBorder(athlete.rank), getRankColor(athlete.rank), athlete.rank <= 3 ? 'bg-slate-800' : 'bg-transparent']">
                                        {{ athlete.rank }}
                                    </div>
                                    <div v-if="athlete.rank <= 3" class="absolute -top-2 -right-2">
                                        <Trophy class="w-6 h-6 drop-shadow-[0_0_8px_rgba(255,255,255,0.3)] animate-pulse"
                                                :class="getRankColor(athlete.rank)" />
                                    </div>
                                </div>

                                <div class="relative shrink-0 z-10">
                                    <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-blue-500 transition-all duration-500 shadow-2xl bg-slate-800 group-hover:shadow-blue-500/20">
                                        <img :src="athlete.profile_image ? `/storage/${athlete.profile_image}` : defaultProfileImage" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 z-10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-blue-500/10 text-blue-400 border border-blue-500/20">Active</span>
                                        <div class="h-px w-8 bg-slate-800"></div>
                                    </div>
                                    <h4 class="text-2xl font-black text-white truncate group-hover:text-blue-400 transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                        <div class="text-slate-400 text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.club }}</div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 px-4 z-10">
                                    <div class="flex flex-col items-end">
                                        <div class="text-3xl font-black text-white tracking-tighter group-hover:text-blue-400 transition-colors">{{ athlete.points }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-widest font-black bg-slate-800/50 px-2 py-0.5 rounded mt-1">PTS</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Show More Button -->
                            <div v-if="maleRankings.length > maleLimit" class="pt-4 flex justify-center">
                                <button 
                                    @click="maleLimit += 10"
                                    class="px-8 py-3 bg-blue-600/20 hover:bg-blue-600 text-blue-400 hover:text-white rounded-xl border border-blue-500/30 hover:border-blue-500 transition-all duration-300 text-xs font-black uppercase tracking-widest"
                                >
                                    Show More
                                </button>
                            </div>

                            <div v-if="maleRankings.length === 0" class="p-8 text-center text-slate-500 italic">No male athletes found</div>
                        </div>
                    </div>

                    <!-- Female Column -->
                    <div class="bg-[#0f172a]/30 rounded-3xl overflow-hidden border border-slate-800/50">
                        <div class="bg-green-600 p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-white uppercase tracking-wider">Female Rankings</h3>
                                <div class="flex items-center gap-2 text-green-100 text-xs mt-1">
                                    <Users class="w-3 h-3" />
                                    <span>{{ femaleRankings.length }} Athletes</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 space-y-3">
                            <div v-for="athlete in visibleFemaleRankings" :key="athlete.id" 
                                 class="flex items-center gap-6 p-4 rounded-3xl bg-slate-900/50 hover:bg-green-600/10 transition-all group cursor-pointer border border-slate-800 hover:border-green-500/30 shadow-lg relative overflow-hidden">
                                <div class="absolute -right-4 -bottom-8 text-8xl font-black text-white/5 italic select-none group-hover:text-green-500/10 transition-colors">
                                    {{ athlete.rank }}
                                </div>

                                <div class="flex flex-col items-center justify-center w-14 shrink-0 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl border-2 flex items-center justify-center text-xl font-black italic transition-all group-hover:scale-110 group-hover:rotate-3 shadow-inner"
                                         :class="[getRankBorder(athlete.rank), getRankColor(athlete.rank), athlete.rank <= 3 ? 'bg-slate-800' : 'bg-transparent']">
                                        {{ athlete.rank }}
                                    </div>
                                    <div v-if="athlete.rank <= 3" class="absolute -top-2 -right-2">
                                        <Trophy class="w-6 h-6 drop-shadow-[0_0_8px_rgba(255,255,255,0.3)] animate-pulse"
                                                :class="getRankColor(athlete.rank)" />
                                    </div>
                                </div>

                                <div class="relative shrink-0 z-10">
                                    <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-green-500 transition-all duration-500 shadow-2xl bg-slate-800 group-hover:shadow-green-500/20">
                                        <img :src="athlete.profile_image ? `/storage/${athlete.profile_image}` : defaultProfileImage" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 z-10">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-green-500/10 text-green-400 border border-green-500/20">Active</span>
                                        <div class="h-px w-8 bg-slate-800"></div>
                                    </div>
                                    <h4 class="text-2xl font-black text-white truncate group-hover:text-green-400 transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                                    <div class="flex items-center gap-2 mt-1">
                                        <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                                        <div class="text-slate-400 text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.club }}</div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 px-4 z-10">
                                    <div class="flex flex-col items-end">
                                        <div class="text-3xl font-black text-white tracking-tighter group-hover:text-green-400 transition-colors">{{ athlete.points }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-widest font-black bg-slate-800/50 px-2 py-0.5 rounded mt-1">PTS</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Show More Button -->
                            <div v-if="femaleRankings.length > femaleLimit" class="pt-4 flex justify-center">
                                <button 
                                    @click="femaleLimit += 10"
                                    class="px-8 py-3 bg-green-600/20 hover:bg-green-600 text-green-400 hover:text-white rounded-xl border border-green-500/30 hover:border-green-500 transition-all duration-300 text-xs font-black uppercase tracking-widest"
                                >
                                    Show More
                                </button>
                            </div>

                            <div v-if="femaleRankings.length === 0" class="p-8 text-center text-slate-500 italic">No female athletes found</div>
                        </div>
                    </div>
                </div>
            </section>
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
