
<script setup>
import { Head, Link } from '@inertiajs/vue3';
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

const route = window.route;

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
const selectedRankingType = ref('Ranking Type');
const selectedSeason = ref('YYYY');
const selectedBelt = ref('Belt Category');
const selectedAcademy = ref('');

const currentView = ref('home');

const availableWeights = computed(() => {
  if (!selectedCategory.value || !selectedGender.value) return [];
  return eventData[selectedCategory.value]?.[selectedGender.value] || [];
});

const rankings = {
  male: [
    { rank: 1, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 2, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1567013127542-490d757e51fc?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 3, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1548690312-e3b507d17a47?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 4, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1583473848882-f9a5bc7fd2ee?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 5, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=400&h=500&auto=format&fit=crop' },
  ],
  female: [
    { rank: 1, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 2, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1594381898411-846e7d193883?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 3, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1628891435222-065923d5e056?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 4, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1609899537878-39d4a5ec7693?q=80&w=400&h=500&auto=format&fit=crop' },
    { rank: 5, name: 'Athlete Name', team: 'Club/Team Name', points: 0.0, image: 'https://images.unsplash.com/photo-1518310383802-640c2de311b2?q=80&w=400&h=500&auto=format&fit=crop' },
  ]
};

const getRankColor = (rank) => {
  if (rank === 1) return 'text-yellow-400';
  if (rank === 2) return 'text-gray-300';
  if (rank === 3) return 'text-amber-600';
  return 'text-slate-500';
};

const getRankBorder = (rank) => {
    if (rank === 1) return 'border-yellow-400';
    if (rank === 2) return 'border-gray-300';
    if (rank === 3) return 'border-amber-600';
    return 'border-slate-700';
}

const navItems = [
    { name: 'Home', view: 'home' },
    { name: 'Anti-doping' },
    { name: 'Championships' },
    { name: 'Rankings', view: 'rankings' },
    { name: 'Academies' },
    { name: 'Athletes' },
    { name: 'Rules' },
    { name: 'News' },
];

</script>

<template>
  <Head title="Kurash Ranking" />
  
  <div class="min-h-screen bg-[#050a14] text-white font-sans selection:bg-yellow-500 selection:text-black">
    <!-- Navbar -->
    <header class="border-b border-white/10 bg-[#050a14] relative z-50">
      <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
        <!-- Logo -->
        <Link :href="route('public.home')" class="flex items-center gap-3">
          <div class="font-black text-2xl tracking-tighter text-yellow-500 flex flex-col leading-none">
            <span>IKF</span>
          </div>
          <div class="h-8 w-px bg-white/20"></div>
          <div class="text-xs font-bold text-white leading-tight tracking-wide">
            INTERNATIONAL<br/>KURASH<br/>FEDERATION
          </div>
        </Link>

        <!-- Navigation -->
        <nav class="hidden lg:flex items-center gap-2 xl:gap-4 text-[10px] xl:text-xs font-bold tracking-widest uppercase h-full">
          <a 
            v-for="item in navItems" 
            :key="item.name"
            href="#" 
            @click.prevent="item.view ? currentView = item.view : null" 
            :class="[
              'relative h-full flex items-center px-4 transition-all duration-300 group whitespace-nowrap',
              currentView === item.view ? 'text-yellow-500' : 'text-gray-400 hover:text-white'
            ]"
          >
            {{ item.name }}
            <span 
              :class="[
                'absolute bottom-0 left-0 h-0.5 bg-yellow-500 transition-all duration-300 ease-out shadow-[0_0_10px_rgba(234,179,8,0.5)]',
                currentView === item.view ? 'w-full' : 'w-0 group-hover:w-full'
              ]"
            ></span>
          </a>
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
      <div class="absolute bottom-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-yellow-500/50 to-transparent"></div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-12 relative">
        <!-- Background Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/20 blur-[100px] rounded-full pointer-events-none"></div>

      <!-- Home View Content -->
      <div v-show="currentView === 'home'">
      <!-- Hero Section -->
      <div class="relative w-full h-[700px] mb-12 rounded-[40px] overflow-hidden group shadow-2xl">
         <!-- Background Image -->
         <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1555597673-b21d5c935865?q=80&w=2000&auto=format&fit=crop')] bg-cover bg-center transition-transform duration-1000 group-hover:scale-105"></div>
         <div class="absolute inset-0 bg-gradient-to-t from-[#050a14] via-[#050a14]/40 to-transparent"></div>
         
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

      <!-- Stats Section -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mb-24 py-12 border-y border-white/5">
          <div class="text-center group cursor-default">
              <div class="text-4xl md:text-5xl font-serif font-bold text-white mb-2 group-hover:text-yellow-500 transition-colors">XX+</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-[0.3em] font-black">Statistic Label</div>
          </div>
          <div class="text-center group cursor-default">
              <div class="text-4xl md:text-5xl font-serif font-bold text-white mb-2 group-hover:text-yellow-500 transition-colors">XXK+</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-[0.3em] font-black">Statistic Label</div>
          </div>
          <div class="text-center group cursor-default">
              <div class="text-4xl md:text-5xl font-serif font-bold text-white mb-2 group-hover:text-yellow-500 transition-colors">XXXX+</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-[0.3em] font-black">Statistic Label</div>
          </div>
          <div class="text-center group cursor-default">
              <div class="text-4xl md:text-5xl font-serif font-bold text-white mb-2 group-hover:text-yellow-500 transition-colors">24/7</div>
              <div class="text-[10px] text-slate-500 uppercase tracking-[0.3em] font-black">Statistic Label</div>
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
              <!-- Event Card 1 -->
              <div class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col">
                  <div class="h-56 bg-slate-800 relative overflow-hidden">
                      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1599058917233-3583e71f462c?q=80&w=600&auto=format&fit=crop')] bg-cover bg-center opacity-70 group-hover:scale-110 transition-transform duration-1000"></div>
                      <div class="absolute top-6 right-6 bg-yellow-500 text-black font-black px-4 py-1 rounded-full text-[10px] uppercase tracking-widest">
                          Label
                      </div>
                      <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] to-transparent opacity-60"></div>
                  </div>
                  <div class="p-8 flex-1 flex flex-col">
                      <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-4">DD/MM - DD/MM, YYYY</div>
                      <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">Event Name Placeholder</h3>
                      <div class="mt-auto flex items-center justify-between">
                          <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                              <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                              City, State
                          </div>
                          <div class="text-white/20 group-hover:text-yellow-500 transition-colors text-2xl">→</div>
                      </div>
                  </div>
              </div>

               <!-- Event Card 2 -->
              <div class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col">
                  <div class="h-56 bg-slate-800 relative overflow-hidden">
                      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=600&auto=format&fit=crop')] bg-cover bg-center opacity-70 group-hover:scale-110 transition-transform duration-1000"></div>
                      <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] to-transparent opacity-60"></div>
                  </div>
                  <div class="p-8 flex-1 flex flex-col">
                      <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-4">DD/MM - DD/MM, YYYY</div>
                      <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">Event Name Placeholder</h3>
                      <div class="mt-auto flex items-center justify-between">
                          <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                              <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                              City, State
                          </div>
                          <div class="text-white/20 group-hover:text-yellow-500 transition-colors text-2xl">→</div>
                      </div>
                  </div>
              </div>

               <!-- Event Card 3 -->
              <div class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col">
                  <div class="h-56 bg-slate-800 relative overflow-hidden">
                      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1591117207239-788cd859dcb7?q=80&w=600&auto=format&fit=crop')] bg-cover bg-center opacity-70 group-hover:scale-110 transition-transform duration-1000"></div>
                      <div class="absolute top-6 right-6 bg-yellow-500 text-black font-black px-4 py-1 rounded-full text-[10px] uppercase tracking-widest">
                          Label
                      </div>
                      <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] to-transparent opacity-60"></div>
                  </div>
                  <div class="p-8 flex-1 flex flex-col">
                      <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-4">DD/MM - DD/MM, YYYY</div>
                      <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">Event Name Placeholder</h3>
                      <div class="mt-auto flex items-center justify-between">
                          <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                              <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                              City, State
                          </div>
                          <div class="text-white/20 group-hover:text-yellow-500 transition-colors text-2xl">→</div>
                      </div>
                  </div>
              </div>

              <!-- Event Card 4 -->
              <div class="group bg-[#0f172a] rounded-[32px] border border-slate-800/50 overflow-hidden hover:border-yellow-500/50 transition-all duration-500 hover:shadow-[0_20px_50px_rgba(234,179,8,0.1)] flex flex-col">
                  <div class="h-56 bg-slate-800 relative overflow-hidden">
                      <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1508672019048-805c876b67e2?q=80&w=600&auto=format&fit=crop')] bg-cover bg-center opacity-70 group-hover:scale-110 transition-transform duration-1000"></div>
                      <div class="absolute inset-0 bg-gradient-to-t from-[#0f172a] to-transparent opacity-60"></div>
                  </div>
                  <div class="p-8 flex-1 flex flex-col">
                      <div class="text-yellow-500 font-black text-[10px] uppercase tracking-[0.2em] mb-4">DD/MM - DD/MM, YYYY</div>
                      <h3 class="text-2xl font-serif font-bold text-white mb-6 group-hover:text-yellow-500 transition-colors leading-tight">Event Name Placeholder</h3>
                      <div class="mt-auto flex items-center justify-between">
                          <div class="flex items-center gap-2 text-slate-400 text-xs font-bold uppercase tracking-widest">
                              <span class="w-2 h-2 rounded-full bg-slate-600"></span>
                              City, State
                          </div>
                          <div class="text-white/20 group-hover:text-yellow-500 transition-colors text-2xl">→</div>
                      </div>
                  </div>
              </div>
          </div>
      </div>

      <!-- Partners Section -->
      <div class="mb-24 py-12 border-y border-white/5 overflow-hidden">
          <div class="text-center mb-10">
              <div class="text-[10px] text-slate-500 uppercase tracking-[0.4em] font-black">Official Global Partners</div>
          </div>
          <div class="flex flex-wrap justify-center items-center gap-12 md:gap-24 opacity-30 hover:opacity-60 transition-opacity duration-500 grayscale hover:grayscale-0">
              <div class="text-2xl font-black tracking-tighter text-white">PARTNER</div>
              <div class="text-2xl font-black tracking-tighter text-white">PARTNER</div>
              <div class="text-2xl font-black tracking-tighter text-white">PARTNER</div>
              <div class="text-2xl font-black tracking-tighter text-white">PARTNER</div>
              <div class="text-2xl font-black tracking-tighter text-white">PARTNER</div>
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

      <!-- Rankings Section (Existing) -->
      <section class="mb-24">
         <div class="text-center mb-12">
            <h2 class="text-4xl font-serif font-bold text-white mb-4">Top 10 Athletes</h2>
            <p class="text-slate-400">Current Standings Season YYYY/YYYY</p>
         </div>

      <!-- Athlete Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mt-12">
           <!-- Male Column -->
           <div class="space-y-4">
               <div class="flex items-center gap-4 mb-8">
                   <h3 class="text-2xl font-bold text-white uppercase tracking-widest">Male</h3>
                   <div class="h-px flex-1 bg-slate-800"></div>
               </div>
               <div v-for="athlete in rankings.male" :key="athlete.rank" class="flex items-center p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/10 group">
                   <div class="w-16 text-center shrink-0">
                       <div class="text-3xl font-black italic" :class="getRankColor(athlete.rank)">#{{ athlete.rank }}</div>
                   </div>
                   <div class="relative mr-8 shrink-0">
                       <div class="w-32 h-44 rounded-lg overflow-hidden border-2 border-slate-600 group-hover:border-blue-500 transition-colors shadow-xl">
                           <img :src="athlete.image" class="w-full h-full object-cover" alt="Athlete" />
                       </div>
                   </div>
                   <div class="flex-1">
                       <div class="text-yellow-500 font-bold text-xs uppercase tracking-widest mb-1">{{ athlete.team }}</div>
                       <h4 class="text-xl font-bold text-white mb-1 group-hover:text-yellow-500 transition-colors">{{ athlete.name }}</h4>
                       <div class="text-slate-400 text-sm font-medium">{{ athlete.points }} pts</div>
                   </div>
               </div>
           </div>
           
           <!-- Female Column -->
           <div class="space-y-4">
               <div class="flex items-center gap-4 mb-8">
                   <h3 class="text-2xl font-bold text-white uppercase tracking-widest">Female</h3>
                   <div class="h-px flex-1 bg-slate-800"></div>
               </div>
               <div v-for="athlete in rankings.female" :key="athlete.rank" class="flex items-center p-4 rounded-xl hover:bg-white/5 transition-colors border border-transparent hover:border-white/10 group">
                   <div class="w-16 text-center shrink-0">
                       <div class="text-3xl font-black italic" :class="getRankColor(athlete.rank)">#{{ athlete.rank }}</div>
                   </div>
                   <div class="relative mr-8 shrink-0">
                       <div class="w-32 h-44 rounded-lg overflow-hidden border-2 border-slate-600 group-hover:border-blue-500 transition-colors shadow-xl">
                           <img :src="athlete.image" class="w-full h-full object-cover" alt="Athlete" />
                       </div>
                   </div>
                   <div class="flex-1">
                       <div class="text-yellow-500 font-bold text-xs uppercase tracking-widest mb-1">{{ athlete.team }}</div>
                       <h4 class="text-xl font-bold text-white mb-1 group-hover:text-yellow-500 transition-colors">{{ athlete.name }}</h4>
                       <div class="text-slate-400 text-sm font-medium">{{ athlete.points }} pts</div>
                   </div>
               </div>
           </div>
      </div>

      <div class="flex justify-center mt-12">
           <button @click="currentView = 'rankings'" class="bg-transparent border border-slate-700 hover:border-yellow-500 text-white hover:text-yellow-500 font-bold py-3 px-8 rounded-lg transition-all uppercase tracking-widest text-sm">
               View Full Rankings
           </button>
      </div>
      </section>

      <!-- Membership & Rules -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-24">
          <!-- Membership -->
          <div class="relative h-80 rounded-3xl overflow-hidden group cursor-pointer">
               <div class="absolute inset-0 bg-[url('/images/membership-bg.jpg')] bg-cover bg-center transition-transform duration-1000 group-hover:scale-110"></div>
               <div class="absolute inset-0 bg-gradient-to-r from-blue-900/90 to-blue-900/40"></div>
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

      <!-- Rankings View Content -->
    <div v-show="currentView === 'rankings'">
      <section class="mb-24">
         <div class="text-center mb-12">
            <h2 class="text-5xl font-serif font-bold text-yellow-500 mb-4 uppercase tracking-wider">World Rankings</h2>
            <p class="text-slate-400 text-lg">Filter athletes by category and view current standings</p>
         </div>

      <!-- Filter Bar (Redesigned) -->
      <div class="bg-[#0f172a]/50 backdrop-blur-md border border-slate-800 rounded-2xl p-8 shadow-2xl mb-16 max-w-5xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
             <!-- Ranking Type -->
             <div class="relative group/select">
                <select v-model="selectedRankingType" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                    <option v-for="type in rankingTypes" :key="type" :value="type">{{ type }}</option>
                </select>
                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
            </div>

            <!-- Weight Category -->
            <div class="relative group/select">
                <select v-model="selectedWeight" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                    <option value="" disabled selected>Weight Category</option>
                    <option v-for="weight in availableWeights" :key="weight.value" :value="weight.value">{{ weight.label }}</option>
                </select>
                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
            </div>

            <!-- Gender -->
            <div class="relative group/select">
                <select v-model="selectedGender" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                    <option value="" disabled selected>Gender</option>
                    <option v-for="gender in genders" :key="gender" :value="gender">{{ gender }}</option>
                </select>
                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
            </div>

            <!-- Club -->
            <div class="relative group/select">
                <select v-model="selectedAcademy" class="w-full bg-[#050a14] border border-slate-700 text-gray-300 rounded-lg px-4 py-3 appearance-none focus:outline-none focus:border-yellow-500 transition-colors cursor-pointer text-sm">
                    <option value="" disabled selected>Club</option>
                    <option v-for="academy in academies" :key="academy" :value="academy">{{ academy }}</option>
                </select>
                <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500 group-hover/select:text-yellow-500 transition-colors pointer-events-none" />
            </div>
        </div>
        
        <button class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-black py-4 px-8 rounded-lg flex items-center justify-center gap-3 transition-all transform hover:scale-[1.01] active:scale-[0.99] shadow-lg text-sm uppercase tracking-[0.2em]">
            <Filter class="w-4 h-4" />
            <span>Apply Filters</span>
        </button>
      </div>

      <!-- Rankings List (Two Columns) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Male Column -->
          <div class="bg-[#0f172a]/30 rounded-3xl overflow-hidden border border-slate-800/50">
              <!-- Column Header -->
              <div class="bg-blue-600 p-6 flex items-center justify-between">
                  <div>
                      <h3 class="text-2xl font-bold text-white uppercase tracking-wider">Male Adult Black Belt</h3>
                      <div class="flex items-center gap-2 text-blue-100 text-xs mt-1">
                          <Users class="w-3 h-3" />
                          <span>8 Athletes</span>
                      </div>
                  </div>
                  <div class="text-right">
                      <div class="text-[10px] text-blue-200 uppercase tracking-widest font-bold">Season YYYY/YYYY</div>
                      <div class="text-[10px] text-blue-100 mt-0.5">Updated: DD/MM/YYYY</div>
                  </div>
              </div>

              <!-- Athlete List -->
              <div class="p-4 space-y-3">
                  <div v-for="athlete in rankings.male" :key="athlete.rank" 
                       class="flex items-center gap-6 p-4 rounded-3xl bg-slate-900/50 hover:bg-blue-600/10 transition-all group cursor-pointer border border-slate-800 hover:border-blue-500/30 shadow-lg relative overflow-hidden">
                      <!-- Decorative Background Number -->
                      <div class="absolute -right-4 -bottom-8 text-8xl font-black text-white/5 italic select-none group-hover:text-blue-500/10 transition-colors">
                          {{ athlete.rank }}
                      </div>

                      <!-- Rank & Trophy -->
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

                      <!-- Athlete Image (Wider/Bigger) -->
                      <div class="relative shrink-0 z-10">
                          <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-blue-500 transition-all duration-500 shadow-2xl bg-slate-800 group-hover:shadow-blue-500/20">
                              <img :src="athlete.image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                          </div>
                          <!-- Nationality/Flag Placeholder -->
                          <div class="absolute -bottom-2 -right-2 w-8 h-6 bg-slate-900 border border-slate-700 rounded-md overflow-hidden flex items-center justify-center shadow-lg">
                              <div class="w-full h-full bg-gradient-to-br from-slate-700 to-slate-800"></div>
                          </div>
                      </div>

                      <!-- Athlete Info -->
                      <div class="flex-1 min-w-0 z-10">
                          <div class="flex items-center gap-2 mb-1">
                              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-blue-500/10 text-blue-400 border border-blue-500/20">Active</span>
                              <div class="h-px w-8 bg-slate-800"></div>
                          </div>
                          <h4 class="text-2xl font-black text-white truncate group-hover:text-blue-400 transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                          <div class="flex items-center gap-2 mt-1">
                              <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                              <div class="text-slate-400 text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.team }}</div>
                          </div>
                      </div>

                      <!-- Points -->
                      <div class="text-right shrink-0 px-4 z-10">
                          <div class="flex flex-col items-end">
                              <div class="text-3xl font-black text-white tracking-tighter group-hover:text-blue-400 transition-colors">{{ athlete.points }}</div>
                              <div class="text-[10px] text-slate-500 uppercase tracking-widest font-black bg-slate-800/50 px-2 py-0.5 rounded mt-1">PTS</div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Show More Button -->
              <div class="p-4 border-t border-slate-800/50">
                  <button class="w-full py-3 rounded-xl bg-blue-600/10 hover:bg-blue-600/20 text-blue-400 font-bold text-sm uppercase tracking-widest transition-all flex items-center justify-center gap-2 group">
                      Show More
                      <ChevronDown class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" />
                  </button>
              </div>
          </div>

          <!-- Female Column -->
          <div class="bg-[#0f172a]/30 rounded-3xl overflow-hidden border border-slate-800/50">
              <!-- Column Header -->
              <div class="bg-green-600 p-6 flex items-center justify-between">
                  <div>
                      <h3 class="text-2xl font-bold text-white uppercase tracking-wider">Female Adult Black Belt</h3>
                      <div class="flex items-center gap-2 text-green-100 text-xs mt-1">
                          <Users class="w-3 h-3" />
                          <span>8 Athletes</span>
                      </div>
                  </div>
                  <div class="text-right">
                      <div class="text-[10px] text-green-200 uppercase tracking-widest font-bold">Season YYYY/YYYY</div>
                      <div class="text-[10px] text-green-100 mt-0.5">Updated: DD/MM/YYYY</div>
                  </div>
              </div>

              <!-- Athlete List -->
              <div class="p-4 space-y-3">
                  <div v-for="athlete in rankings.female" :key="athlete.rank" 
                       class="flex items-center gap-6 p-4 rounded-3xl bg-slate-900/50 hover:bg-green-600/10 transition-all group cursor-pointer border border-slate-800 hover:border-green-500/30 shadow-lg relative overflow-hidden">
                      <!-- Decorative Background Number -->
                      <div class="absolute -right-4 -bottom-8 text-8xl font-black text-white/5 italic select-none group-hover:text-green-500/10 transition-colors">
                          {{ athlete.rank }}
                      </div>

                      <!-- Rank & Trophy -->
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

                      <!-- Athlete Image (Wider/Bigger) -->
                      <div class="relative shrink-0 z-10">
                          <div class="w-32 h-36 rounded-2xl overflow-hidden border-2 border-slate-700 group-hover:border-green-500 transition-all duration-500 shadow-2xl bg-slate-800 group-hover:shadow-green-500/20">
                              <img :src="athlete.image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="Athlete" />
                          </div>
                          <!-- Nationality/Flag Placeholder -->
                          <div class="absolute -bottom-2 -right-2 w-8 h-6 bg-slate-900 border border-slate-700 rounded-md overflow-hidden flex items-center justify-center shadow-lg">
                              <div class="w-full h-full bg-gradient-to-br from-slate-700 to-slate-800"></div>
                          </div>
                      </div>

                      <!-- Athlete Info -->
                      <div class="flex-1 min-w-0 z-10">
                          <div class="flex items-center gap-2 mb-1">
                              <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-tighter bg-green-500/10 text-green-400 border border-green-500/20">Active</span>
                              <div class="h-px w-8 bg-slate-800"></div>
                          </div>
                          <h4 class="text-2xl font-black text-white truncate group-hover:text-green-400 transition-colors tracking-tight italic uppercase">{{ athlete.name }}</h4>
                          <div class="flex items-center gap-2 mt-1">
                              <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                              <div class="text-slate-400 text-xs uppercase tracking-[0.2em] font-medium truncate">{{ athlete.team }}</div>
                          </div>
                      </div>

                      <!-- Points -->
                      <div class="text-right shrink-0 px-4 z-10">
                          <div class="flex flex-col items-end">
                              <div class="text-3xl font-black text-white tracking-tighter group-hover:text-green-400 transition-colors">{{ athlete.points }}</div>
                              <div class="text-[10px] text-slate-500 uppercase tracking-widest font-black bg-slate-800/50 px-2 py-0.5 rounded mt-1">PTS</div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Show More Button -->
              <div class="p-4 border-t border-slate-800/50">
                  <button class="w-full py-3 rounded-xl bg-green-600/10 hover:bg-green-600/20 text-green-400 font-bold text-sm uppercase tracking-widest transition-all flex items-center justify-center gap-2 group">
                      Show More
                      <ChevronDown class="w-4 h-4 group-hover:translate-y-0.5 transition-transform" />
                  </button>
              </div>
          </div>
      </div>
      </section>
    </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-[#020617] border-t-2 border-yellow-500/50 py-8 text-center text-slate-500 text-sm">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>&copy; YYYY International Federation Name. All rights reserved.</div>
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
