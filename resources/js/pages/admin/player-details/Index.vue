<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ListFilter,
    Search,
    Users,
    Mail,
    Phone,
    MapPin,
    Calendar,
    User
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import Pagination from '@/components/Pagination.vue';
import { debounce } from 'lodash';

const props = defineProps<{
    players: {
        data: any[];
        links: any[];
        meta: any;
    };
    filters: {
        search: string;
        gender: string;
    };
}>();

const search = ref(props.filters.search || '');
const genderFilter = ref(props.filters.gender || 'all');

// Debounce search to avoid too many requests
const updateSearch = debounce((value: string) => {
    router.get(
        route('admin.player-details.index'),
        { search: value, gender: genderFilter.value },
        { preserveState: true, replace: true, preserveScroll: true }
    );
}, 300);

watch(search, (value) => {
    updateSearch(value);
});

const setGenderFilter = (gender: string) => {
    genderFilter.value = gender;
    router.get(
        route('admin.player-details.index'),
        { search: search.value, gender: gender },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};

const breadcrumbs = [
    { title: 'Player Details', href: route('admin.player-details.index') },
];

const formatDate = (dateString: string) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Player Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4 pt-0">
            <div class="flex items-center justify-between space-y-2">
                <h2 class="text-3xl font-bold tracking-tight">Player Details</h2>
            </div>
            
            <Card class="border-border bg-card">
                <CardHeader class="p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <CardTitle class="text-lg font-semibold text-card-foreground">Detailed Information</CardTitle>
                            <CardDescription>View contact and personal details for all players.</CardDescription>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    type="search"
                                    placeholder="Search details..."
                                    class="pl-9 h-9 w-full bg-background border-border"
                                />
                            </div>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" size="sm" class="h-9 gap-2 border-border bg-background text-foreground">
                                        <ListFilter class="h-3.5 w-3.5" />
                                        <span class="sr-only sm:not-sr-only">Filter</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="bg-popover border-border">
                                    <DropdownMenuLabel class="text-popover-foreground">Filter by Gender</DropdownMenuLabel>
                                    <DropdownMenuSeparator class="bg-border" />
                                    <DropdownMenuCheckboxItem 
                                        :checked="genderFilter === 'all'"
                                        @click="setGenderFilter('all')"
                                        class="focus:bg-accent focus:text-accent-foreground"
                                    >
                                        All
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuCheckboxItem 
                                        :checked="genderFilter === 'Male'"
                                        @click="setGenderFilter('Male')"
                                        class="focus:bg-accent focus:text-accent-foreground"
                                    >
                                        Male
                                    </DropdownMenuCheckboxItem>
                                    <DropdownMenuCheckboxItem 
                                        :checked="genderFilter === 'Female'"
                                        @click="setGenderFilter('Female')"
                                        class="focus:bg-accent focus:text-accent-foreground"
                                    >
                                        Female
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="relative w-full overflow-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted border-b border-border hover:bg-muted">
                                    <TableHead class="pl-6 h-12 text-muted-foreground font-medium">Player</TableHead>
                                    <TableHead class="h-12 text-muted-foreground font-medium">Address</TableHead>
                                    <TableHead class="h-12 text-muted-foreground font-medium">Email Address</TableHead>
                                    <TableHead class="h-12 text-muted-foreground font-medium">Contact Person</TableHead>
                                    <TableHead class="h-12 text-muted-foreground font-medium">Phone Number</TableHead>
                                    <TableHead class="h-12 text-muted-foreground font-medium">Birthday</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="player in players.data" 
                                    :key="player.id"
                                    class="border-b border-border hover:bg-muted/50 transition-colors"
                                >
                                    <TableCell class="pl-6 py-4 font-medium text-foreground">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-full bg-muted flex items-center justify-center overflow-hidden border border-border">
                                                <img 
                                                    v-if="player.profile_image" 
                                                    :src="`/storage/${player.profile_image}`" 
                                                    :alt="player.full_name"
                                                    class="h-full w-full object-cover" 
                                                />
                                                <User v-else class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ player.full_name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ player.club || 'No Club' }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <MapPin class="h-3.5 w-3.5 text-muted-foreground/70" />
                                            <span class="truncate max-w-50" :title="player.address">{{ player.address || '-' }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Mail class="h-3.5 w-3.5 text-muted-foreground/70" />
                                            <span class="truncate max-w-50" :title="player.email">{{ player.email || '-' }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <User class="h-3.5 w-3.5 text-muted-foreground/70" />
                                            <span>{{ player.emergency_contact || '-' }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Phone class="h-3.5 w-3.5 text-muted-foreground/70" />
                                            <span>{{ player.emergency_contact_number || '-' }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 text-muted-foreground/70" />
                                            <span>{{ formatDate(player.birthday) }}</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="players.data.length === 0">
                                    <TableCell colspan="6" class="p-8 text-center text-muted-foreground">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <Users class="h-8 w-8 text-muted-foreground/50" />
                                            <p>No players found matching your criteria.</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="border-t border-border p-4">
                        <Pagination :links="players.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
