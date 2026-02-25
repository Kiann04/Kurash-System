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
    <Head title="World Rankings | Kurash Federation" />
    <div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
        <!-- Navbar -->
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

            <section class="mb-24">
                <div class="text-center mb-12">
                    <h2 class="text-5xl font-serif font-bold text-yellow-500 mb-4 uppercase tracking-wider">World Rankings</h2>
                    <p class="text-slate-400 text-lg">Official international Kurash player standings</p>
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
                            <div v-for="athlete in maleRankings" :key="athlete.id" 
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
                            <div v-for="athlete in femaleRankings" :key="athlete.id" 
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
                            <div v-if="femaleRankings.length === 0" class="p-8 text-center text-slate-500 italic">No female athletes found</div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-[#020617] border-t-2 border-yellow-500/50 py-8 text-center text-slate-500 text-sm">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
                <div>&copy; 2026 International Kurash Federation. All rights reserved.</div>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-yellow-500 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-yellow-500 transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-yellow-500 transition-colors">Contact</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@300;400;500;600;700&display=swap');

.font-serif {
    font-family: 'Cinzel', serif;
}
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>
