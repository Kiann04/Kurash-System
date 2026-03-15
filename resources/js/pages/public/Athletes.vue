<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

declare global {
  interface Window {
    route: any;
  }
}

const route = window.route;
import { Search } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import Pagination from '@/components/Pagination.vue'

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

const activeTab = ref('All')
const searchQuery = ref('')
const tabs = ['All', 'Male', 'Female']

const filteredPlayers = computed(() => {
    return props.players.data.filter(player => {
        const search = searchQuery.value.toLowerCase()
        const matchesSearch = player.full_name.toLowerCase().includes(search) || 
                            (player.club && player.club.toLowerCase().includes(search))
        
        let matchesTab = true
        if (activeTab.value !== 'All') {
            matchesTab = player.gender.toLowerCase() === activeTab.value.toLowerCase()
        }
        
        return matchesSearch && matchesTab
    })
})

const defaultProfileImage = '/images/default-profile.svg'

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
    <div class="min-h-screen bg-background text-foreground font-sans relative" style="--background: hsl(222 47% 6%)">
        <Head title="Athletes" />

        <!-- Navbar -->
        <nav class="fixed top-0 left-0 right-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/40 transition-all duration-300">
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
                            item.route === 'public.athletes.index' ? 'text-accent' : 'text-muted-foreground hover:text-foreground'
                        ]"
                    >
                        {{ item.name }}
                        <span 
                            :class="[
                                'absolute bottom-0 left-0 h-0.5 bg-accent transition-all duration-300 ease-out shadow-[0_0_10px] shadow-accent/50',
                                item.route === 'public.athletes.index' ? 'w-full' : 'w-0 group-hover:w-full'
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
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </button>
                    <button class="text-muted-foreground hover:text-foreground transition-all duration-300 transform hover:scale-110 active:scale-95">
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-px bg-linear-to-r from-transparent via-accent/50 to-transparent"></div>
        </nav>

    <main class="max-w-7xl mx-auto px-4 py-16 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-primary/10 blur-[100px] rounded-full pointer-events-none"></div>

        <!-- Header -->
        <div class="mb-12 relative z-10">
            <div class="flex flex-col items-center text-center gap-6 border-b border-border pb-12">
                <div class="max-w-4xl mx-auto flex flex-col items-center">
                    <div class="flex items-center gap-3 mb-4 justify-center">
                        <div class="h-px w-8 bg-accent"></div>
                        <span class="text-accent font-black uppercase tracking-[0.3em] text-[10px]">Official Database</span>
                        <div class="h-px w-8 bg-accent"></div>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-serif font-bold text-foreground mb-6 leading-tight tracking-tight uppercase">
                        <span class="text-accent italic">Athletes</span>
                    </h1>
                    <p class="text-muted-foreground text-lg max-w-2xl leading-relaxed font-light">
                        Discover the champions of Kurash. Browse profiles, rankings, and achievements of international competitors.
                    </p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12 bg-card/50 p-2 rounded-2xl border border-border/50 backdrop-blur-sm relative z-10">
            <!-- Tabs -->
            <div class="flex items-center bg-card rounded-xl p-1 gap-1 w-full md:w-auto overflow-x-auto">
                <button v-for="tab in tabs" :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'px-6 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all duration-300 whitespace-nowrap', 
                        activeTab === tab ? 'bg-accent text-accent-foreground shadow-lg shadow-accent/20' : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                    ]">
                    {{ tab }}
                </button>
            </div>

            <!-- Search -->
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="text-[10px] font-black text-muted-foreground uppercase tracking-widest hidden lg:block mr-4">
                    Total: <span class="text-foreground">{{ filteredPlayers.length }}</span>
                </div>
                <div class="relative group w-full md:w-64">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-accent transition-colors" />
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="Search athletes..." 
                        class="w-full bg-card border border-border rounded-xl py-2.5 pl-10 pr-4 text-sm text-foreground placeholder-muted-foreground focus:outline-none focus:border-accent/50 focus:ring-1 focus:ring-accent/50 transition-all" 
                    />
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div v-if="filteredPlayers.length === 0" class="py-24 text-center border border-dashed border-border rounded-5xl">
            <p class="text-muted-foreground italic text-xl">No athletes found matching your criteria.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <div v-for="player in filteredPlayers" :key="player.id" 
                 class="group bg-card rounded-4xl border border-border overflow-hidden hover:border-accent/50 transition-all duration-500 hover:shadow-[0_20px_50px] hover:shadow-accent/10 flex flex-col">
                <div class="h-64 bg-muted relative overflow-hidden">
                    <img 
                        :src="player.profile_image ? `/storage/${player.profile_image}` : defaultProfileImage" 
                        alt="Profile" 
                        class="w-full h-full object-cover opacity-80 group-hover:scale-110 transition-transform duration-1000"
                    />
                    <div class="absolute inset-0 bg-linear-to-t from-card to-transparent opacity-60"></div>
                    <div class="absolute bottom-4 left-6">
                        <div class="text-accent font-black text-[10px] uppercase tracking-[0.2em] mb-1">{{ player.gender }}</div>
                        <h3 class="text-2xl font-serif font-bold text-foreground group-hover:text-accent transition-colors leading-tight">{{ player.full_name }}</h3>
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col justify-between">
                    <div class="flex items-center gap-3 text-muted-foreground text-xs font-bold uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-secondary"></span>
                        {{ player.club ?? 'Independent' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 flex justify-center">
            <Pagination :links="props.players.links" />
        </div>
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
