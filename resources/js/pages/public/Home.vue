
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

declare global {
  interface Window {
    route: any;
  }
}

const route = window.route || ((name: string) => name);
import { 
  Search, 
  Instagram, 
  Facebook, 
  Trophy, 
  Users, 
  Filter, 
  ChevronDown,
  Medal
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface EventItem {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_date: string;
    end_date: string | null;
    image_path: string | null;
    status: string;
}

const props = defineProps<{
    players?: any[]
    events?: EventItem[]
}>();

const selectedEvent = ref<EventItem | null>(null);

const openEvent = (event: EventItem) => {
    selectedEvent.value = event;
};

const closeEvent = () => {
    selectedEvent.value = null;
};

const getRankColor = (rank: number) => {
    if (rank === 1) return 'text-yellow-400'
    if (rank === 2) return 'text-gray-300'
    if (rank === 3) return 'text-amber-600'
    return 'text-slate-500'
}

const rankings = computed(() => {
    const players = props.players || [];
    const male = players
        .filter(p => p.gender?.toLowerCase() === 'male')
        .sort((a, b) => b.points - a.points)
        .slice(0, 5)
        .map((p, index) => ({
            rank: index + 1,
            name: p.full_name || p.name,
            team: p.club,
            points: p.points || 0,
            image: p.profile_image ? `/storage/${p.profile_image}` : '/images/default-profile.svg'
        }));

    const female = players
        .filter(p => p.gender?.toLowerCase() === 'female')
        .sort((a, b) => b.points - a.points)
        .slice(0, 5)
        .map((p, index) => ({
            rank: index + 1,
            name: p.full_name || p.name,
            team: p.club,
            points: p.points || 0,
            image: p.profile_image ? `/storage/${p.profile_image}` : '/images/default-profile.svg'
        }));

    return { male, female };
});

const formatEventDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        month: 'short',
        day: '2-digit',
    });
};

const formatEventYear = (date: string) => {
    return new Date(date).getFullYear();
};

const formatEventRange = (start: string, end: string | null) => {
    if (!end || end === start) {
        return `${formatEventDate(start)}, ${formatEventYear(start)}`;
    }

    return `${formatEventDate(start)} - ${formatEventDate(end)}, ${formatEventYear(end)}`;
};

const eventData = {
  'Kids': {
    'Male': [
      { label: '4-7 yrs: 19kg', value: '19kg' },
      { label: '4-7 yrs: +19kg', value: '+19kg' },
      { label: '8-11 yrs: 21kg', value: '21kg' },
      { label: '8-11 yrs: 25kg', value: '25kg' },
      { label: '8-11 yrs: 30kg', value: '30kg' },
      { label: '8-11 yrs: 33kg', value: '33kg' },
      { label: '8-11 yrs: +33kg', value: '+33kg' },
      { label: '12-13 yrs: 36kg', value: '36kg' },
      { label: '12-13 yrs: 44kg', value: '44kg' },
      { label: '12-13 yrs: 52kg', value: '52kg' },
      { label: '12-13 yrs: 60kg', value: '60kg' },
      { label: '12-13 yrs: 65kg', value: '65kg' },
      { label: '12-13 yrs: 70kg', value: '70kg' },
      { label: '12-13 yrs: +70kg', value: '+70kg' },
    ],
    'Female': [
      { label: '4-7 yrs: 17kg', value: '17kg' },
      { label: '4-7 yrs: +17kg', value: '+17kg' },
      { label: '8-11 yrs: 20kg', value: '20kg' },
      { label: '8-11 yrs: 24kg', value: '24kg' },
      { label: '8-11 yrs: 27kg', value: '27kg' },
      { label: '8-11 yrs: +27kg', value: '+27kg' },
      { label: '12-13 yrs: 30kg', value: '30kg' },
      { label: '12-13 yrs: 36kg', value: '36kg' },
      { label: '12-13 yrs: 40kg', value: '40kg' },
      { label: '12-13 yrs: 44kg', value: '44kg' },
      { label: '12-13 yrs: 48kg', value: '48kg' },
      { label: '12-13 yrs: 52kg', value: '52kg' },
      { label: '12-13 yrs: 57kg', value: '57kg' },
      { label: '12-13 yrs: +57kg', value: '+57kg' },
    ]
  },
  'Cadets': {
    'Male': ['46kg', '50kg', '55kg', '60kg', '65kg', '71kg', '77kg', '83kg', '+83kg'].map(w => ({ label: w, value: w })),
    'Female': ['40kg', '44kg', '48kg', '52kg', '57kg', '63kg', '+63kg'].map(w => ({ label: w, value: w }))
  },
  'Juniors': {
    'Male': ['50kg', '55kg', '60kg', '65kg', '71kg', '77kg', '83kg', '90kg', '+90kg'].map(w => ({ label: w, value: w })),
    'Female': ['44kg', '48kg', '52kg', '57kg', '63kg', '70kg', '+70kg'].map(w => ({ label: w, value: w }))
  },
  'Team': {
    'Male': [{ label: '14-14/16-17 yrs old', value: '14-14/16-17 yrs old' }],
    'Female': [{ label: '14-14/16-17 yrs old', value: '14-14/16-17 yrs old' }]
  }
};

const categories = Object.keys(eventData);
const genders = ['Male', 'Female'];
const rankingTypes = ['Ranking Type', 'Ranking Type', 'Ranking Type'];
const seasons = ['YYYY', 'YYYY', 'YYYY', 'YYYY'];
const belts = ['Belt Category', 'Belt Category', 'Belt Category', 'Belt Category', 'Belt Category'];
const academies = ['All Clubs', 'Club Name', 'Club Name', 'Club Name'];

const selectedCategory = ref('');
const selectedGender = ref('');
const selectedWeight = ref('');

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
  <Head title="Kurash Ranking" />
  
  <div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar -->
    <header class="border-b border-white/10 bg-[#050a14] relative z-50">
      <div class="max-w-360 mx-auto px-8 h-20 flex items-center justify-between">
        <!-- Logo -->
        <a :href="route('public.home')" class="flex items-center gap-3">
          <img src="/images/ksf-logo.png" alt="KSF Logo" class="h-12 w-auto" />
          <div class="h-8 w-px bg-white/20"></div>
          <div class="text-xs font-bold text-white leading-tight tracking-wide">
            KURASH<br/>SPORTS<br/>FEDERATION 
          </div>
        </a>

        <!-- Navigation -->
        <nav class="hidden lg:flex items-center gap-x-2 xl:gap-x-4 text-[10px] xl:text-xs font-bold tracking-wider uppercase h-full font-serif">
          <template v-for="item in navItems" :key="item.name">
            <a 
              v-if="item.route"
              :href="route(item.route)"
              :class="[
                'relative h-full flex items-center px-3 xl:px-4 transition-all duration-300 group whitespace-nowrap',
                item.route === 'public.home' ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
              ]"
            >
              {{ item.name }}
              <span 
                :class="[
                  'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                  item.route === 'public.home' ? 'w-full' : 'w-0 group-hover:w-full'
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

        <!-- Actions -->
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
      <!-- Gold Line -->
      <div class="absolute bottom-0 left-0 w-full h-px bg-linear-to-r from-transparent via-yellow-500/50 to-transparent"></div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/20 blur-[100px] rounded-full pointer-events-none"></div>

      <!-- Home View Content -->
      <div class="space-y-24">
        <!-- Hero Section -->
        <div class="relative w-full h-175 mb-12 rounded-4xl overflow-hidden group shadow-2xl">
           <!-- Background Image -->
           <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1555597673-b21d5c935865?q=80&w=2000&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-1000 group-hover:scale-105"></div>
           <div class="absolute inset-0 bg-linear-to-t from-[#050a14] via-[#050a14]/40 to-transparent"></div>
           
           <!-- Content -->
        <div class="absolute bottom-0 left-0 w-full p-10 md:p-20 z-10">
            <div class="max-w-4xl">
                <div class="flex items-center gap-4 mb-6">
                   <div class="h-px w-12 bg-yellow-500"></div>
                   <div class="text-yellow-500 font-black uppercase tracking-[0.3em] text-sm">International Federation Name</div>
                </div>
                <h1 class="text-6xl md:text-8xl font-serif font-bold text-white mb-8 leading-[0.9] drop-shadow-2xl">
                    PROTOTYPE <br/> 
                    <span class="text-yellow-500 italic">MAIN</span> TITLE
                </h1>
                <p class="text-slate-200 text-xl md:text-2xl max-w-2xl mb-12 leading-relaxed font-light drop-shadow-lg">
                    A brief descriptive sentence about the federation or sport goes here. This is a prototype for the client to review.
                </p>
                 <div class="flex flex-wrap gap-6">
                     <button class="bg-yellow-500 hover:bg-yellow-400 text-black font-black py-5 px-12 rounded-2xl transition-all transform hover:scale-105 shadow-[0_0_40px_rgba(234,179,8,0.4)] uppercase tracking-widest text-sm">
                         Call to Action
                     </button>
                     <button class="bg-white/5 hover:bg-white/10 backdrop-blur-md text-white border border-white/20 font-black py-5 px-12 rounded-2xl transition-all uppercase tracking-widest text-sm flex items-center gap-3 group">
                         Secondary Action
                         <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center group-hover:bg-yellow-500 group-hover:text-black transition-colors">
                            <span class="ml-1 text-[10px]">▶</span>
                         </div>
                     </button>
                 </div>
             </div>
         </div>
      </div>

      <!-- Upcoming Events -->
      <div class="mb-24">
          <div class="flex items-end justify-between mb-12 border-b border-slate-800 pb-6">
              <div>
                  <h2 class="text-4xl font-serif font-bold text-white mb-2">Upcoming Events</h2>
                  <p class="text-slate-400">Official Event Calendar</p>
              </div>
              <a href="#" class="text-yellow-500 hover:text-white transition-colors font-bold uppercase tracking-widest text-sm flex items-center gap-2 group">
                  View Calendar 
                  <span class="transform group-hover:translate-x-1 transition-transform">→</span>
              </a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div
                  v-for="event in (props.events || [])"
                  :key="event.id"
                  class="group bg-[#0f172a] rounded-4xl border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col cursor-pointer"
                  @click="openEvent(event)"
              >
                  <div class="h-56 bg-slate-800 relative overflow-hidden">
                      <div class="absolute inset-0 flex items-center justify-center bg-slate-950">
                          <img
                              v-if="event.image_path"
                              :src="event.image_path"
                              alt="Event image"
                              class="max-h-full max-w-full object-contain transition-transform duration-1000 group-hover:scale-105"
                          />
                          <div
                              v-else
                              class="h-full w-full bg-linear-to-br from-slate-800 via-slate-900 to-slate-950"
                          ></div>
                      </div>
                      <div class="absolute top-6 right-6 bg-yellow-500 text-black font-black px-4 py-1 rounded-full text-[10px] uppercase tracking-widest">
                          Event
                      </div>
                      <div class="absolute inset-0 bg-linear-to-t from-[#0f172a] to-transparent opacity-60"></div>
                  </div>
                  <div class="p-8 flex-1 flex flex-col">
                      <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-4">
                          {{ formatEventRange(event.start_date, event.end_date) }}
                      </div>
                      <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">
                          {{ event.title }}
                      </h3>
                      <div class="mt-auto flex items-center justify-between">
                          <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                              <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                              {{ event.location || 'TBA' }}
                          </div>
                          <div class="text-white/20 group-hover:text-yellow-500 transition-colors text-2xl">→</div>
                      </div>
                  </div>
              </div>

              <div
                  v-if="(props.events || []).length === 0"
                  class="col-span-full p-12 text-center text-slate-500 italic bg-[#0f172a]/30 rounded-3xl border border-dashed border-slate-800"
              >
                  No upcoming events yet.
              </div>
          </div>
      </div>

      <!-- Event Modal -->
      <div
          v-if="selectedEvent"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-6"
          @click.self="closeEvent"
      >
          <div class="w-full max-w-6xl rounded-3xl overflow-hidden border border-slate-700 bg-[#0b1220] shadow-2xl">
              <div class="relative flex flex-col md:flex-row min-h-[36rem]">
                  <!-- Left: Image / Slogan -->
                  <div class="relative md:w-3/5 h-72 md:h-auto bg-slate-900">
                      <div class="absolute inset-0 flex items-center justify-center bg-slate-950">
                          <img
                              v-if="selectedEvent.image_path"
                              :src="selectedEvent.image_path"
                              alt="Event image"
                              class="max-h-full max-w-full object-contain"
                          />
                          <div
                              v-else
                              class="h-full w-full bg-linear-to-br from-slate-900 via-slate-900/80 to-slate-950"
                          ></div>
                      </div>
                      <div class="absolute inset-0 bg-linear-to-t from-black/70 via-black/30 to-transparent"></div>
                      <div class="absolute bottom-6 left-6 right-6">
                          <div class="text-yellow-500 font-black uppercase tracking-[0.3em] text-[10px]">
                              Kurash Federation
                          </div>
                          <div class="text-white font-serif text-2xl md:text-3xl mt-2">
                              {{ selectedEvent.title }}
                          </div>
                      </div>
                  </div>

                  <!-- Right: Info -->
                  <div class="md:w-2/5 p-8 md:p-10 space-y-6">
                      <div class="flex flex-wrap items-center gap-3">
                          <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.3em]">
                              {{ formatEventRange(selectedEvent.start_date, selectedEvent.end_date) }}
                          </div>
                          <div class="text-slate-500 text-xs uppercase tracking-widest">
                              {{ selectedEvent.status }}
                          </div>
                      </div>
                      <h3 class="text-3xl md:text-4xl font-serif font-bold text-white">
                          {{ selectedEvent.title }}
                      </h3>
                      <p class="text-slate-300 text-base leading-relaxed">
                          {{ selectedEvent.description || 'No description available.' }}
                      </p>
                      <div class="text-slate-300 text-sm uppercase tracking-widest">
                          Location: <span class="text-white">{{ selectedEvent.location || 'TBA' }}</span>
                      </div>
                      <div class="flex flex-wrap gap-4 pt-2">
                          <a
                              :href="route('public.tournaments.index')"
                              class="bg-yellow-500 hover:bg-yellow-400 text-black font-black py-3 px-6 rounded-xl transition-all uppercase tracking-widest text-xs"
                          >
                              Register
                          </a>
                          <button
                              class="bg-white/5 hover:bg-white/10 text-white border border-white/20 font-black py-3 px-6 rounded-xl transition-all uppercase tracking-widest text-xs"
                              @click="closeEvent"
                          >
                              Close
                          </button>
                      </div>
                  </div>

                  <button
                      class="absolute top-4 right-4 h-10 w-10 rounded-full bg-black/50 text-white hover:bg-black/70 transition-colors"
                      @click="closeEvent"
                      aria-label="Close event"
                  >
                      &times;
                  </button>
              </div>
          </div>
      </div>
      <!-- Latest News -->
      <div class="mb-24">
          <div class="flex items-end justify-between mb-12 border-b border-slate-800 pb-6">
              <div>
                  <h2 class="text-4xl font-serif font-bold text-white mb-2">Latest News</h2>
                  <p class="text-slate-400">Updates from the Federation</p>
              </div>
              <a href="#" class="text-yellow-500 hover:text-white transition-colors font-bold uppercase tracking-widest text-sm flex items-center gap-2 group">
                  View All News 
                  <span class="transform group-hover:translate-x-1 transition-transform">→</span>
              </a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
               <!-- News 1 -->
               <div class="group cursor-pointer">
                   <div class="rounded-2xl overflow-hidden mb-6 relative">
                       <div class="aspect-video bg-[url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=800&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-700 group-hover:scale-110"></div>
                       <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                   </div>
                   <div class="text-yellow-500 font-bold text-xs uppercase tracking-widest mb-3">Category • X min read</div>
                   <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-yellow-500 transition-colors leading-tight">
                       Headline for News or Announcement YYYY
                   </h3>
                   <p class="text-slate-400 line-clamp-2">
                       This is a placeholder for a news description or press release summary. It should be replaced with actual content.
                   </p>
               </div>

               <!-- News 2 -->
                <div class="group cursor-pointer">
                    <div class="rounded-2xl overflow-hidden mb-6 relative">
                        <div class="aspect-video bg-[url('https://images.unsplash.com/photo-1526506118085-60ce8714f8c5?q=80&w=800&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-700 group-hover:scale-110"></div>
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                    </div>
                    <div class="text-yellow-500 font-bold text-xs uppercase tracking-widest mb-3">Category • DD/MM/YYYY</div>
                    <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-yellow-500 transition-colors leading-tight">
                        News Title Placeholder for Prototype
                    </h3>
                    <p class="text-slate-400 line-clamp-2">
                        This is a placeholder for a news description or press release summary. It should be replaced with actual content.
                    </p>
                </div>

                <!-- News 3 -->
                <div class="group cursor-pointer">
                    <div class="rounded-2xl overflow-hidden mb-6 relative">
                        <div class="aspect-video bg-[url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=800&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-700 group-hover:scale-110"></div>
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/0 transition-colors"></div>
                    </div>
                    <div class="text-yellow-500 font-bold text-xs uppercase tracking-widest mb-3">Category • DD/MM/YYYY</div>
                    <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-yellow-500 transition-colors leading-tight">
                        News Title Placeholder for Prototype
                    </h3>
                    <p class="text-slate-400 line-clamp-2">
                        This is a placeholder for a news description or press release summary. It should be replaced with actual content.
                    </p>
                </div>
          </div>
          
          <div class="flex justify-center mt-12">
               <button class="bg-transparent border border-slate-700 hover:border-yellow-500 text-white hover:text-yellow-500 font-bold py-3 px-8 rounded-lg transition-all uppercase tracking-widest text-sm">
                   Load More News
               </button>
          </div>
      </div>

      <!-- Rankings Section -->
      <section class="mb-24 relative overflow-hidden">
         <!-- Section Header -->
         <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
            <div class="relative">
                <div class="flex items-center gap-3 text-yellow-500 font-black uppercase tracking-[0.3em] text-[10px] mb-4">
                   <div class="h-px w-8 bg-yellow-500"></div>
                   Local Standings
                </div>
                <h2 class="text-5xl md:text-6xl font-serif font-bold text-white leading-none">
                    TOP 5 <span class="text-yellow-500 italic">ATHLETES</span>
                </h2>
            </div>
            <a :href="route('public.rankings.index')" class="bg-white/5 hover:bg-white/10 backdrop-blur-md text-white border border-white/20 font-black py-4 px-10 rounded-2xl transition-all uppercase tracking-widest text-xs flex items-center gap-3 group">
                View Full Rankings
                <span class="transform group-hover:translate-x-1 transition-transform">→</span>
            </a>
         </div>

         <!-- Athlete Grid -->
         <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
           <!-- Male Column (Blue) -->
           <div class="relative group">
               <!-- Header Decor -->
               <div class="flex items-center gap-4 mb-10">
                   <div class="w-12 h-12 rounded-2xl bg-blue-600/20 flex items-center justify-center border border-blue-600/30">
                       <Trophy class="w-6 h-6 text-blue-500" />
                   </div>
                   <h3 class="text-3xl font-serif font-bold text-white uppercase tracking-wider italic">Male</h3>
                   <div class="h-px flex-1 bg-linear-to-r from-blue-600/50 to-transparent"></div>
               </div>

               <div class="space-y-4">
                   <div v-for="athlete in rankings.male" :key="athlete.rank" 
                        class="relative flex items-center p-4 rounded-3xl bg-[#0f172a]/50 border border-slate-800/50 hover:border-blue-500/50 transition-all duration-500 group/card hover:shadow-[0_15px_40px_rgba(37,99,235,0.15)] overflow-hidden">
                       <!-- Rank Badge -->
                       <div class="w-14 text-center shrink-0 relative z-10">
                           <div class="text-3xl font-black italic text-blue-500/30 group-hover/card:text-blue-500 transition-colors">#{{ athlete.rank }}</div>
                       </div>
                       <!-- Photo -->
                       <div class="relative mx-6 shrink-0 z-10">
                           <div class="w-24 h-32 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover/card:border-blue-500 transition-all duration-500 shadow-xl bg-slate-800">
                               <img :src="athlete.image" class="w-full h-full object-cover grayscale group-hover/card:grayscale-0 transition-all duration-700 group-hover/card:scale-110" alt="Athlete" />
                           </div>
                       </div>
                       <!-- Info -->
                       <div class="flex-1 z-10">
                           <div class="text-blue-400 font-bold text-[10px] uppercase tracking-widest mb-1 opacity-70">{{ athlete.team }}</div>
                           <h4 class="text-2xl font-serif font-bold text-white mb-2 group-hover/card:text-blue-400 transition-colors tracking-tight italic">{{ athlete.name }}</h4>
                           <div class="flex items-center gap-3">
                               <div class="px-3 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-500 text-[10px] font-black uppercase tracking-widest">
                                   {{ athlete.points }} PTS
                               </div>
                           </div>
                       </div>
                       <!-- Decorative background text -->
                        <div class="absolute -right-4 -bottom-4 text-6xl font-black text-white/2 italic select-none group-hover/card:text-blue-500/5 transition-colors uppercase">
                            {{ athlete.rank }}
                        </div>
                   </div>
                   <div v-if="rankings.male.length === 0" class="p-12 text-center text-slate-500 italic bg-[#0f172a]/30 rounded-3xl border border-dashed border-slate-800">
                       No male athletes found
                   </div>
               </div>
           </div>
           
           <!-- Female Column (Green) -->
           <div class="relative group">
               <!-- Header Decor -->
               <div class="flex items-center gap-4 mb-10">
                   <div class="w-12 h-12 rounded-2xl bg-emerald-600/20 flex items-center justify-center border border-emerald-600/30">
                       <Trophy class="w-6 h-6 text-emerald-500" />
                   </div>
                   <h3 class="text-3xl font-serif font-bold text-white uppercase tracking-wider italic">Female</h3>
                   <div class="h-px flex-1 bg-linear-to-r from-emerald-600/50 to-transparent"></div>
               </div>

               <div class="space-y-4">
                   <div v-for="athlete in rankings.female" :key="athlete.rank" 
                        class="relative flex items-center p-4 rounded-3xl bg-[#0f172a]/50 border border-slate-800/50 hover:border-emerald-500/50 transition-all duration-500 group/card hover:shadow-[0_15px_40px_rgba(16,185,129,0.15)] overflow-hidden">
                       <!-- Rank Badge -->
                       <div class="w-14 text-center shrink-0 relative z-10">
                           <div class="text-3xl font-black italic text-emerald-500/30 group-hover/card:text-emerald-500 transition-colors">#{{ athlete.rank }}</div>
                       </div>
                       <!-- Photo -->
                       <div class="relative mx-6 shrink-0 z-10">
                           <div class="w-24 h-32 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover/card:border-emerald-500 transition-all duration-500 shadow-xl bg-slate-800">
                               <img :src="athlete.image" class="w-full h-full object-cover grayscale group-hover/card:grayscale-0 transition-all duration-700 group-hover/card:scale-110" alt="Athlete" />
                           </div>
                       </div>
                       <!-- Info -->
                       <div class="flex-1 z-10">
                           <div class="text-emerald-400 font-bold text-[10px] uppercase tracking-widest mb-1 opacity-70">{{ athlete.team }}</div>
                           <h4 class="text-2xl font-serif font-bold text-white mb-2 group-hover/card:text-emerald-400 transition-colors tracking-tight italic">{{ athlete.name }}</h4>
                           <div class="flex items-center gap-3">
                               <div class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-[10px] font-black uppercase tracking-widest">
                                   {{ athlete.points }} PTS
                               </div>
                           </div>
                       </div>
                       <!-- Decorative background text -->
                        <div class="absolute -right-4 -bottom-4 text-6xl font-black text-white/2 italic select-none group-hover/card:text-emerald-500/5 transition-colors uppercase">
                            {{ athlete.rank }}
                        </div>
                   </div>
                   <div v-if="rankings.female.length === 0" class="p-12 text-center text-slate-500 italic bg-[#0f172a]/30 rounded-3xl border border-dashed border-slate-800">
                       No female athletes found
                   </div>
               </div>
           </div>
         </div>
      </section>

      <!-- Membership & Rules -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-24">
          <!-- Membership -->
          <div class="relative h-80 rounded-3xl overflow-hidden group cursor-pointer">
               <div class="absolute inset-0 bg-[url('/images/membership-bg.jpg')] bg-cover bg-center transition-transform duration-1000 group-hover:scale-110"></div>
               <div class="absolute inset-0 bg-linear-to-r from-blue-900/90 to-blue-900/40"></div>
               <div class="absolute inset-0 flex flex-col justify-center p-10">
                   <h3 class="text-3xl font-serif font-bold text-white mb-4">Membership</h3>
                   <p class="text-blue-100 max-w-md mb-8 text-lg">
                       The athletes registered with IKF will have their graduation recognized by an official sport organization.
                   </p>
                   <div class="flex items-center gap-2 text-yellow-500 font-bold uppercase tracking-widest group-hover:translate-x-2 transition-transform">
                       Join Now <span class="text-xl">→</span>
                   </div>
               </div>
               <!-- Card Graphic Overlay -->
               <div class="absolute -right-10 -bottom-10 w-64 h-40 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 transform -rotate-12 group-hover:-rotate-6 transition-transform duration-500"></div>
          </div>

          <!-- Rules -->
          <div class="relative h-80 rounded-3xl overflow-hidden group cursor-pointer">
               <div class="absolute inset-0 bg-slate-200 bg-cover bg-center transition-transform duration-1000 group-hover:scale-110"></div>
               <div class="absolute inset-0 flex flex-col justify-center p-10">
                   <h3 class="text-3xl font-serif font-bold text-slate-900 mb-4">Rules</h3>
                   <p class="text-slate-600 max-w-md mb-8 text-lg">
                       Download the IKF Rule Book (V6.0). Understand the standards of competition.
                   </p>
                   <div class="flex items-center gap-2 text-blue-900 font-bold uppercase tracking-widest group-hover:translate-x-2 transition-transform">
                       Download PDF <span class="text-xl">→</span>
                   </div>
               </div>
               <!-- Book Graphic Overlay -->
               <div class="absolute -right-4 top-1/2 -translate-y-1/2 w-48 h-64 bg-white shadow-2xl rounded-l-lg transform group-hover:-translate-x-4 transition-transform duration-500 border-l-4 border-blue-900"></div>
          </div>
      </div>
      </div>
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



