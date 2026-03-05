<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
import { Users, UserCheck, UserX, ArrowRight, Shield, Calendar } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

interface Player {
    id: number;
    name: string;
    gender: string;
    age: number;
    club: string;
    expiry_date: string;
    status: string;
}

defineProps<{
    totalPlayers: number;
    activePlayers: number;
    inactivePlayers: number;
    recentPlayers: Player[];
}>();

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-8 p-6 dark:bg-slate-950">
            <!-- Stats Overview -->
            <div class="grid gap-6 md:grid-cols-3">
                <Card class="relative overflow-hidden border-l-4 border-l-blue-500 shadow-sm hover:shadow-md transition-all dark:bg-slate-950 dark:border-slate-800 dark:border-l-blue-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Players</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center dark:bg-blue-900/30">
                            <Users class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ totalPlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Registered athletes</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden border-l-4 border-l-green-500 shadow-sm hover:shadow-md transition-all dark:bg-slate-950 dark:border-slate-800 dark:border-l-green-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Active Players</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center dark:bg-green-900/30">
                            <UserCheck class="h-4 w-4 text-green-600 dark:text-green-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ activePlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Currently eligible</p>
                    </CardContent>
                </Card>

                <Card class="relative overflow-hidden border-l-4 border-l-red-500 shadow-sm hover:shadow-md transition-all dark:bg-slate-950 dark:border-slate-800 dark:border-l-red-500">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Inactive Players</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center dark:bg-red-900/30">
                            <UserX class="h-4 w-4 text-red-600 dark:text-red-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ inactivePlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Expired or suspended</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity Section -->
            <div class="grid gap-6">
                <Card class="col-span-1 shadow-sm border-slate-200 dark:border-slate-800 dark:bg-slate-950">
                    <CardHeader class="flex flex-row items-center justify-between border-b px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800">
                        <div class="space-y-1">
                            <CardTitle class="text-lg font-semibold text-slate-900 dark:text-slate-100">Recent Registrations</CardTitle>
                            <CardDescription>Latest athletes added to the system.</CardDescription>
                        </div>
                        <Link :href="route('admin.players.index')">
                            <Button variant="ghost" size="sm" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 group dark:text-blue-400 dark:hover:text-blue-300 dark:hover:bg-blue-900/30">
                                View All Players
                                <ArrowRight class="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </Button>
                        </Link>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="relative w-full overflow-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-slate-50 dark:bg-slate-900">
                                        <TableHead class="px-6 py-4 dark:text-slate-400">Athlete</TableHead>
                                        <TableHead class="px-6 py-4 dark:text-slate-400">Club / Team</TableHead>
                                        <TableHead class="px-6 py-4 dark:text-slate-400">Age</TableHead>
                                        <TableHead class="px-6 py-4 dark:text-slate-400">Gender</TableHead>
                                        <TableHead class="px-6 py-4 dark:text-slate-400">Membership Date</TableHead>
                                        <TableHead class="px-6 py-4 text-center dark:text-slate-400">Membership Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="player in recentPlayers" :key="player.id" class="hover:bg-slate-50/80 transition-colors dark:hover:bg-slate-900/80 dark:border-slate-800">
                                        <TableCell class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-9 w-9 border border-slate-200 dark:border-slate-700">
                                                    <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.name}&background=random`" :alt="player.name" />
                                                    <AvatarFallback class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ getInitials(player.name) }}</AvatarFallback>
                                                </Avatar>
                                                <div class="flex flex-col">
                                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ player.name }}</span>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">ID: #{{ player.id.toString().padStart(4, '0') }}</span>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                                <Shield class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" />
                                                <span>{{ player.club }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-6 py-4">
                                            <span class="font-medium text-slate-900 dark:text-slate-100">{{ player.age }} years</span>
                                        </TableCell>
                                        <TableCell class="px-6 py-4">
                                            <span class="text-xs text-slate-500 uppercase dark:text-slate-400">{{ player.gender }}</span>
                                        </TableCell>
                                        <TableCell class="px-6 py-4">
                                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                                <Calendar class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" />
                                                <span>{{ player.expiry_date }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="px-6 py-4 text-center">
                                            <Badge :variant="player.status === 'active' ? 'default' : 'destructive'" 
                                                :class="[
                                                    'capitalize shadow-none font-normal',
                                                    player.status === 'active' ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50' : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50'
                                                ]">
                                                {{ player.status }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-if="recentPlayers.length === 0">
                                        <TableCell colspan="5" class="p-8 text-center text-muted-foreground">
                                            <div class="flex flex-col items-center justify-center gap-2">
                                                <Users class="h-8 w-8 text-slate-300 dark:text-slate-600" />
                                                <p>No recent registrations found.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
