<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { route } from 'ziggy-js';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Trophy, 
    Calendar, 
    Users, 
    MapPin, 
    Search, 
    Filter,
    ArrowLeft,
    Edit,
    Dumbbell,
    Swords
} from 'lucide-vue-next';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Player {
    id: number;
    full_name: string;
    gender: 'male' | 'female';
    club?: string;
    age_category: string;
    weigh_in_weight?: number | null;
    weight_category_id?: number | null;
}

interface WeightCategory {
    id: number;
    gender: 'male' | 'female';
    name: string;
}

interface Tournament {
    id: number;
    name: string;
    tournament_date: string;
    status: string;
}

const props = defineProps<{
    tournament: Tournament;
    players: Player[];
    weightCategories: WeightCategory[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: props.tournament.name, href: '' },
];

/* ================= REACTIVE STATES ================= */
const selectedGender = ref<string>('all');
const selectedAge = ref<string>('all');
const search = ref('');

// Pagination
const currentPage = ref(1);
const perPage = 20;

/* ================= FILTERS ================= */
const filteredPlayers = computed(() => {
    let list = props.players;

    // Gender filter
    if (selectedGender.value !== 'all') {
        list = list.filter(
            p => p.gender?.toLowerCase() === selectedGender.value.toLowerCase()
        );
    }

    // Age category filter
    if (selectedAge.value !== 'all') {
        list = list.filter(p => p.age_category === selectedAge.value);
    }

    // Search filter
    if (search.value) {
        const query = search.value.toLowerCase();
        list = list.filter(
            p =>
                p.full_name.toLowerCase().includes(query) ||
                (p.club?.toLowerCase().includes(query) ?? false)
        );
    }

    return list;
});

// Paginated slice
const paginatedPlayers = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    return filteredPlayers.value.slice(start, start + perPage);
});

const totalPages = computed(() =>
    Math.ceil(filteredPlayers.value.length / perPage)
);

/* ================= WEIGHT CLASS ================= */
const getWeightClass = (player: Player) => {
    if (!player.weight_category_id) return '-';
    const matched = props.weightCategories.find(w => w.id === player.weight_category_id);
    return matched ? matched.name : '-';
};

/* ================= AGE CATEGORIES ================= */
const ageCategories = computed(() => {
    const cats = new Set(props.players.map(p => p.age_category).filter(Boolean));
    return Array.from(cats).sort();
});

const generating = ref(false);

const generateBrackets = () => {
    if (generating.value) return;

    generating.value = true;

    router.post(route('admin.tournaments.brackets.generate', props.tournament.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            generating.value = false;
        },
    });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getStatusColor = (status: string) => {
    switch(status.toLowerCase()) {
        case 'open': return 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20';
        case 'ongoing': return 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20';
        case 'completed': return 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700';
        default: return 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20'; // draft
    }
};

const stats = computed(() => [
    {
        title: 'Total Players',
        value: props.players.length,
        icon: Users,
        color: 'text-blue-600 dark:text-blue-400',
        bg: 'bg-blue-50 dark:bg-blue-900/20'
    },
    {
        title: 'Clubs Participating',
        value: new Set(props.players.map(p => p.club).filter(Boolean)).size,
        icon: MapPin,
        color: 'text-indigo-600 dark:text-indigo-400',
        bg: 'bg-indigo-50 dark:bg-indigo-900/20'
    },
    {
        title: 'Weight Classes',
        value: props.weightCategories.length,
        icon: Dumbbell,
        color: 'text-emerald-600 dark:text-emerald-400',
        bg: 'bg-emerald-50 dark:bg-emerald-900/20'
    }
]);

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>

<template>
    <Head :title="`Tournament: ${props.tournament.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            
            <!-- Header Section -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <Link :href="route('admin.tournaments.index')">
                        <Button variant="ghost" size="icon" class="mt-1">
                            <ArrowLeft class="h-5 w-5 text-muted-foreground" />
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ props.tournament.name }}</h1>
                            <Badge :class="['capitalize shadow-none font-normal', getStatusColor(props.tournament.status)]">
                                {{ props.tournament.status }}
                            </Badge>
                        </div>
                        <div class="flex items-center gap-2 mt-1 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ formatDate(props.tournament.tournament_date) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('admin.tournaments.edit', props.tournament.id)">
                        <Button variant="outline" class="gap-2">
                            <Edit class="h-4 w-4" />
                            Edit Details
                        </Button>
                    </Link>
                    <Button 
                        class="gap-2" 
                        :disabled="generating"
                        @click="generateBrackets"
                    >
                        <Swords class="h-4 w-4" />
                        {{ generating ? 'Generating...' : 'Generate Brackets' }}
                    </Button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card v-for="stat in stats" :key="stat.title" class="shadow-sm border-slate-200 dark:border-slate-800 dark:bg-slate-900">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </CardTitle>
                        <div :class="['h-8 w-8 rounded-lg flex items-center justify-center', stat.bg]">
                            <component :is="stat.icon" :class="['h-4 w-4', stat.color]" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold dark:text-white">{{ stat.value }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Content Area -->
            <Card class="border-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <CardTitle class="dark:text-white">Registered Players</CardTitle>
                        
                        <!-- Filters -->
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative w-full md:w-64">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search player or club..."
                                    class="pl-9 h-9 bg-background border-slate-200 dark:border-slate-800 dark:bg-slate-900"
                                />
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" size="sm" class="h-9 gap-2 border-slate-200 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
                                        <Filter class="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>Filters</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-56">
                                    <DropdownMenuLabel>Gender</DropdownMenuLabel>
                                    <DropdownMenuRadioGroup v-model="selectedGender">
                                        <DropdownMenuRadioItem value="all">All Genders</DropdownMenuRadioItem>
                                        <DropdownMenuRadioItem value="male">Male</DropdownMenuRadioItem>
                                        <DropdownMenuRadioItem value="female">Female</DropdownMenuRadioItem>
                                    </DropdownMenuRadioGroup>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuLabel>Age Category</DropdownMenuLabel>
                                    <DropdownMenuRadioGroup v-model="selectedAge">
                                        <DropdownMenuRadioItem value="all">All Ages</DropdownMenuRadioItem>
                                        <DropdownMenuRadioItem v-for="age in ageCategories" :key="age" :value="age">
                                            {{ age }}
                                        </DropdownMenuRadioItem>
                                    </DropdownMenuRadioGroup>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="rounded-md border border-slate-200 dark:border-slate-800 overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent">
                                    <TableHead class="w-62.5">Player</TableHead>
                                    <TableHead>Club</TableHead>
                                    <TableHead class="text-center">Gender</TableHead>
                                    <TableHead>Age Category</TableHead>
                                    <TableHead class="text-center">Weight (kg)</TableHead>
                                    <TableHead>Assigned Class</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="player in paginatedPlayers" 
                                    :key="player.id"
                                    class="hover:bg-muted/50"
                                >
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-8 w-8 border border-slate-200 dark:border-slate-700">
                                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.full_name}&background=random`" />
                                                <AvatarFallback>{{ getInitials(player.full_name) }}</AvatarFallback>
                                            </Avatar>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-medium text-foreground dark:text-slate-100">{{ player.full_name }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ player.club || '-' }}</TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="capitalize font-normal text-xs">
                                            {{ player.gender }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{{ player.age_category }}</TableCell>
                                    <TableCell class="text-center font-mono text-xs">
                                        {{ player.weigh_in_weight ?? '-' }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <span :class="{'text-muted-foreground': !player.weight_category_id, 'font-medium text-blue-600 dark:text-blue-400': player.weight_category_id}">
                                                {{ getWeightClass(player) }}
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="paginatedPlayers.length === 0">
                                    <TableCell colspan="6" class="h-24 text-center text-muted-foreground">
                                        No players found matching your filters.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-4">
                        <div class="text-sm text-muted-foreground">
                            Page {{ currentPage }} of {{ totalPages || 1 }}
                        </div>
                        <div class="flex gap-2">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                                class="dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                            >
                                Previous
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                :disabled="currentPage === totalPages || totalPages === 0"
                                @click="currentPage++"
                                class="dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
