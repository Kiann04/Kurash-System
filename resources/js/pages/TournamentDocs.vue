<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Printer } from 'lucide-vue-next'

const form = ref({
    tournamentName: '1st International Kurash Open Championship',
    date: '2026-02-20',
    venue: 'Central Asian Kurash Arena, Tashkent',
    organizer: 'International Kurash Association (IKA)',
    matNumber: '1'
})

const matches = ref([
    { match: 1, division: 'Male - -66kg', red: 'AARON DIAZ', blue: 'BRYCE SANTOS', club: 'PHILIPPINES' },
    { match: 2, division: 'Male - -66kg', red: 'CALVIN REYES', blue: 'DYLAN CRUZ', club: 'PHILIPPINES' },
    { match: 3, division: 'Male - -66kg', red: 'ELI NAVARRO', blue: 'FELIX GOMEZ', club: 'PHILIPPINES' },
    { match: 4, division: 'Male - -66kg', red: 'GAVIN MENDOZA', blue: 'HAYDEN FLORES', club: 'PHILIPPINES' },
    { match: 5, division: 'Male - -66kg', red: 'IVAN TORRES', blue: 'JARED LIM', club: 'PHILIPPINES' },
    { match: 6, division: 'Male - -66kg', red: 'KYLE BAUTISTA', blue: 'LOGAN CASTILLO', club: 'PHILIPPINES' },
    { match: 7, division: 'Male - -66kg', red: 'MARCUS AQUINO', blue: 'NATHANIEL ONG', club: 'PHILIPPINES' },
    { match: 8, division: 'Male - -66kg', red: 'TEST', blue: 'TEST', club: 'PHILIPPINES' },
    { match: 9, division: 'Male - -66kg', red: 'TEST', blue: 'TEST12', club: 'PHILIPPINES' },
])

const props = defineProps<{
    tournament?: {
        name: string
        date: string
        venue?: string
        organizer?: string
    }
}>()

const handlePrint = () => {
    window.print()
}

onMounted(() => {
    // Pre-fill form if query params or props exist
    const params = new URLSearchParams(window.location.search)
    if (params.get('name')) form.value.tournamentName = params.get('name') || ''
    if (params.get('date')) form.value.date = params.get('date') || ''
    if (params.get('venue')) form.value.venue = params.get('venue') || ''
    if (params.get('organizer')) form.value.organizer = params.get('organizer') || ''
    
    // Also check props if passed via Inertia
    if (props.tournament) {
        form.value.tournamentName = props.tournament.name
        form.value.date = props.tournament.date
        if (props.tournament.venue) form.value.venue = props.tournament.venue
        if (props.tournament.organizer) form.value.organizer = props.tournament.organizer
    }
})
</script>

<template>
    <Head title="Tournament Docs" />

    <AppLayout>
        <div class="p-6 space-y-6 print:p-0">
            
            <!-- Header & Configuration (Hidden on Print) -->
            <div class="space-y-6 print:hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Tournament Document Generator</h1>
                        <p class="text-muted-foreground">Configure and print professional tournament documentation.</p>
                    </div>
                    <Button @click="handlePrint">
                        <Printer class="w-4 h-4 mr-2" />
                        Print to PDF
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Document Configuration</CardTitle>
                        <CardDescription>Enter details to populate the official documents.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-2">
                            <Label>Tournament Name</Label>
                            <Input v-model="form.tournamentName" />
                        </div>
                        <div class="space-y-2">
                            <Label>Date</Label>
                            <Input type="date" v-model="form.date" />
                        </div>
                        <div class="space-y-2">
                            <Label>Venue</Label>
                            <Input v-model="form.venue" />
                        </div>
                        <div class="space-y-2">
                            <Label>Organizer</Label>
                            <Input v-model="form.organizer" />
                        </div>
                        <div class="space-y-2">
                            <Label>Mat Number</Label>
                            <Input v-model="form.matNumber" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- DOCUMENT PREVIEW AREA -->
            <div class="bg-white text-black shadow-lg mx-auto max-w-[210mm] print:shadow-none print:max-w-none print:w-full print:mx-0">
                
                <!-- PAGE 1: TITLE PAGE -->
                <div class="p-12 min-h-[297mm] flex flex-col justify-between relative print:break-after-page">
                    <!-- Watermark -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none">
                        <svg viewBox="0 0 24 24" class="w-96 h-96 fill-current">
                            <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                        </svg>
                    </div>

                    <div class="text-center space-y-2 mt-20">
                        <p class="text-xs tracking-[0.2em] text-gray-500 uppercase font-medium">Official Documentation</p>
                        <h1 class="text-4xl font-bold uppercase tracking-tight">{{ form.tournamentName }}</h1>
                    </div>

                    <div class="grid grid-cols-2 gap-12 mt-20">
                        <div class="space-y-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Date of Competition</p>
                            <p class="font-bold text-lg">{{ form.date }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Competition Venue</p>
                            <p class="font-bold text-lg">{{ form.venue }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Organizing Committee</p>
                            <p class="font-bold text-lg">{{ form.organizer }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">Mat Assignment</p>
                            <p class="font-bold text-lg text-gray-100 bg-gray-900 inline-block px-2 py-0.5 rounded">MAT #{{ form.matNumber }}</p>
                        </div>
                    </div>

                    <div class="mt-auto mb-20 space-y-8">
                        <div class="border-t-2 border-dashed border-gray-300 w-full"></div>
                        
                        <div class="text-center">
                            <h3 class="text-sm font-bold uppercase tracking-wider mb-12">Referee Panel</h3>
                            
                            <div class="flex justify-between items-end gap-12">
                                <div class="flex-1 space-y-8">
                                    <div class="border-b border-gray-800 pb-1"></div>
                                    <p class="text-[10px] uppercase">Chief Referee Signature</p>
                                    
                                    <div class="border-b border-gray-800 pb-1 mt-8"></div>
                                    <p class="text-[10px] uppercase">Secretary Signature</p>
                                </div>
                                
                                <div class="opacity-10">
                                    <svg viewBox="0 0 24 24" class="w-24 h-24 fill-current">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                                    </svg>
                                </div>

                                <div class="flex-1 space-y-8">
                                    <div class="border-b border-gray-800 pb-1"></div>
                                    <p class="text-[10px] uppercase">Side Referee 1 Signature</p>
                                    
                                    <div class="border-b border-gray-800 pb-1 mt-8"></div>
                                    <p class="text-[10px] uppercase">Side Referee 2 Signature</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAGE 2: CONTEST ORDER SHEET -->
                <div class="p-8 min-h-[297mm] flex flex-col relative print:break-after-page">
                    <!-- Header -->
                    <div class="flex justify-between items-start border-b-2 border-black pb-4 mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-gray-300">
                                <svg viewBox="0 0 24 24" class="w-8 h-8 fill-current"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold uppercase">Contest Order Sheet</h2>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">{{ form.tournamentName }} | MAT #{{ form.matNumber }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold">DATE: {{ form.date }}</p>
                            <p class="text-[10px] text-gray-500">KURASH FEDERATION STANDARD</p>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="flex-1">
                        <table class="w-full border-collapse border border-black text-[10px]">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th rowspan="2" class="border border-black p-2 w-12">Match #</th>
                                    <th rowspan="2" class="border border-black p-2 w-24">Division</th>
                                    <th rowspan="2" class="border border-black p-2">Athlete (Red / Blue)</th>
                                    <th rowspan="2" class="border border-black p-2 w-32">Club/Country</th>
                                    <th colspan="3" class="border border-black p-1 bg-green-50 text-[9px]">Points</th>
                                    <th rowspan="2" class="border border-black p-2 bg-red-50 w-20">Penalties</th>
                                    <th rowspan="2" class="border border-black p-2 w-20">Winner</th>
                                </tr>
                                <tr class="bg-gray-50 text-[9px]">
                                    <th class="border border-black p-1 w-10">Halal</th>
                                    <th class="border border-black p-1 w-10">Yonbosh</th>
                                    <th class="border border-black p-1 w-10">Chala</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="match in matches" :key="match.match">
                                    <tr class="h-10">
                                        <td rowspan="2" class="border border-black text-center font-bold">{{ match.match }}</td>
                                        <td rowspan="2" class="border border-black text-center text-gray-600">{{ match.division }}</td>
                                        
                                        <!-- RED FIGHTER -->
                                        <td class="border-r border-black p-1 pl-2 text-red-700 font-bold text-xs">{{ match.red }}</td>
                                        <td class="border-r border-black p-1 text-gray-600 uppercase">{{ match.club }}</td>
                                        
                                        <!-- Points (Empty for manual entry) -->
                                        <td class="border border-black"></td>
                                        <td class="border border-black"></td>
                                        <td class="border border-black"></td>
                                        
                                        <!-- Penalties -->
                                        <td rowspan="2" class="border border-black"></td>
                                        
                                        <!-- Winner -->
                                        <td rowspan="2" class="border border-black"></td>
                                    </tr>
                                    <tr class="h-10">
                                        <!-- BLUE FIGHTER -->
                                        <td class="border-r border-black border-b border-black p-1 pl-2 text-blue-700 font-bold text-xs">{{ match.blue }}</td>
                                        <td class="border-r border-black border-b border-black p-1 text-gray-600 uppercase">{{ match.club }}</td>
                                        
                                        <!-- Points (Empty for manual entry) -->
                                        <td class="border border-black"></td>
                                        <td class="border border-black"></td>
                                        <td class="border border-black"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer -->
                    <div class="mt-8 flex justify-between items-end">
                        <div class="w-64 border-b border-black pb-1">
                            <p class="text-[10px] text-gray-500 mb-4">Referee Signature</p>
                        </div>
                        <div class="w-64 border-b border-black pb-1 text-right">
                            <p class="text-[10px] text-gray-500 mb-4">Match Time Secretary</p>
                        </div>
                    </div>
                </div>

                <!-- PAGE 3: SCOREBOARD LAYOUT & COMMAND LOG -->
                <div class="p-12 min-h-[297mm] flex flex-col relative print:break-after-page">
                    
                    <!-- Header -->
                    <div class="flex items-center gap-4 mb-12">
                        <div class="w-10 h-10 border-2 border-gray-200 rounded flex items-center justify-center opacity-30">
                            <svg viewBox="0 0 24 24" class="w-6 h-6"><path d="M4 6h16M4 12h16m-7 6h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </div>
                        <h2 class="text-2xl font-bold uppercase tracking-wide">Official Scoreboard Layout</h2>
                        <div class="ml-auto bg-black text-white px-3 py-1 text-sm font-mono rounded">MAT #{{ form.matNumber }}</div>
                    </div>

                    <!-- Scoreboard Visual -->
                    <div class="bg-gray-900 text-white p-8 rounded-3xl shadow-2xl mb-12 border-4 border-gray-800">
                        <div class="grid grid-cols-3 gap-8 mb-8">
                            <!-- RED PLAYER -->
                            <div class="bg-red-600 rounded-xl p-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-2 opacity-20 font-bold text-4xl italic">RED</div>
                                <p class="text-[10px] opacity-80 font-bold tracking-wider mb-1">ATHLETE RED (JAKA)</p>
                                <h3 class="text-3xl font-bold mb-1 truncate">AKMALOV TEMUR</h3>
                                <p class="text-xs opacity-75">UZBEKISTAN</p>
                            </div>

                            <!-- TIMER -->
                            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 flex flex-col items-center justify-center relative">
                                <div class="text-gray-400 mb-2">
                                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-none stroke-current stroke-2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                </div>
                                <div class="text-6xl font-mono font-bold tracking-widest tabular-nums">03:45</div>
                                <div class="mt-4 px-3 py-1 bg-green-600 text-[10px] font-bold rounded-full uppercase tracking-wider">Running</div>
                            </div>

                            <!-- BLUE PLAYER -->
                            <div class="bg-blue-600 rounded-xl p-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-2 opacity-20 font-bold text-4xl italic">BLUE</div>
                                <p class="text-[10px] opacity-80 font-bold tracking-wider mb-1">ATHLETE BLUE (JAKA)</p>
                                <h3 class="text-3xl font-bold mb-1 truncate">SANTOS KIAN</h3>
                                <p class="text-xs opacity-75">PHILIPPINES</p>
                            </div>
                        </div>

                        <!-- SCORES -->
                        <div class="grid grid-cols-2 gap-12">
                            <!-- RED SCORES -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-yellow-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Halal</p>
                                    <p class="text-4xl font-bold">0</p>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-green-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Yonbosh</p>
                                    <p class="text-4xl font-bold">1</p>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-blue-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Chala</p>
                                    <p class="text-4xl font-bold">0</p>
                                </div>
                            </div>

                            <!-- BLUE SCORES -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-yellow-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Halal</p>
                                    <p class="text-4xl font-bold">0</p>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-green-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Yonbosh</p>
                                    <p class="text-4xl font-bold">0</p>
                                </div>
                                <div class="bg-gray-800 rounded-lg p-4 text-center border-b-4 border-blue-500">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2">Chala</p>
                                    <p class="text-4xl font-bold">2</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer / Penalties -->
                        <div class="mt-8 flex justify-between items-center opacity-50">
                            <div class="flex gap-2">
                                <div class="w-8 h-8 bg-yellow-600 rounded flex items-center justify-center font-bold text-black">T</div>
                                <div class="w-8 h-8 bg-gray-700 rounded border border-gray-600"></div>
                                <div class="w-8 h-8 bg-gray-700 rounded border border-gray-600"></div>
                            </div>
                            <p class="text-[10px] uppercase tracking-[0.2em] font-light">Referee Management Interface</p>
                            <div class="flex gap-2">
                                <div class="w-8 h-8 bg-gray-700 rounded border border-gray-600"></div>
                                <div class="w-8 h-8 bg-gray-700 rounded border border-gray-600"></div>
                                <div class="w-8 h-8 bg-gray-700 rounded border border-gray-600"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Command Log & Result -->
                    <div class="grid grid-cols-2 gap-8">
                        <div class="border rounded-xl p-6 bg-gray-50">
                            <h3 class="text-xs font-bold uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                Referee Command Log
                            </h3>
                            <div class="space-y-3 font-mono text-[10px]">
                                <div class="flex gap-4 text-gray-400">
                                    <span>04:00</span>
                                    <span class="font-bold text-gray-800">TOXTAL</span>
                                    <span>- Match Start</span>
                                </div>
                                <div class="flex gap-4">
                                    <span class="text-gray-400">03:42</span>
                                    <span class="font-bold text-green-600 border-l-2 border-green-500 pl-2">YONBOSH</span>
                                    <span class="text-gray-600">- RED Athlete</span>
                                </div>
                                <div class="flex gap-4">
                                    <span class="text-gray-400">02:15</span>
                                    <span class="font-bold text-yellow-600 border-l-2 border-yellow-500 pl-2">TANBEH</span>
                                    <span class="text-gray-600">- BLUE Athlete</span>
                                </div>
                                <div class="flex gap-4">
                                    <span class="text-gray-400">01:05</span>
                                    <span class="font-bold text-blue-600 border-l-2 border-blue-500 pl-2">CHALA</span>
                                    <span class="text-gray-600">- BLUE Athlete</span>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-xl p-6 bg-gray-50 flex flex-col items-center justify-center text-center relative overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center opacity-[0.05] pointer-events-none">
                                <svg viewBox="0 0 24 24" class="w-32 h-32 fill-current">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zm0 9l2.5-1.25L12 8.5l-2.5 1.25L12 11zm0 2.5l-5-2.5-5 2.5L12 22l10-8.5-5-2.5-5 2.5z"/>
                                </svg>
                            </div>
                            
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-2">Match Result</p>
                            <h3 class="text-2xl font-bold mb-1">AKMALOV TEMUR</h3>
                            <div class="px-3 py-1 bg-gray-200 text-gray-600 text-[10px] font-bold rounded-full uppercase tracking-wider">Winner by Points</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 0;
        size: auto;
    }
    
    body * {
        visibility: hidden;
    }

    /* Show only the print area and its children */
    #app, 
    #app > div, /* AppLayout wrapper */
    .print\:p-0,
    .print\:p-0 * {
        visibility: visible;
    }
    
    /* Ensure backgrounds print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Page breaks */
    .print\:break-after-page {
        break-after: page;
        page-break-after: always;
    }

    /* Hide sidebar and headers */
    nav, header, aside, [data-slot="sidebar"] {
        display: none !important;
    }
}
</style>