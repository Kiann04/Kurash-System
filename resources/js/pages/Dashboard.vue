<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
import { Users, UserCheck, UserX, ArrowRight, Calendar, Clock, Trophy, LayoutGrid } from 'lucide-vue-next';
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
    club_location: string;
    membership_start: string;
    membership_end: string;
    status: string;
}

defineProps<{
    totalPlayers: number;
    activePlayers: number;
    expiringPlayers: number;
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
        <div class="flex flex-1 flex-col gap-8 p-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                    <LayoutGrid class="h-6 w-6 text-primary" />
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-3xl font-bold tracking-tight text-foreground">Dashboard</h1>
                    <p class="text-muted-foreground">Overview of the Kurash Federation system status.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Players -->
                <Card class="relative overflow-hidden border-l-4 border-l-primary shadow-sm hover:shadow-md transition-all">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Athletes</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                            <Users class="h-4 w-4 text-primary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-foreground">{{ totalPlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Registered in system</p>
                    </CardContent>
                </Card>

                <!-- Active Members -->
                <Card class="relative overflow-hidden border-l-4 border-l-secondary shadow-sm hover:shadow-md transition-all">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Active Members</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-secondary/10 flex items-center justify-center">
                            <UserCheck class="h-4 w-4 text-secondary" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-foreground">{{ activePlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Currently eligible</p>
                    </CardContent>
                </Card>

                <!-- Expiring Members -->
                <Card class="relative overflow-hidden border-l-4 border-l-accent shadow-sm hover:shadow-md transition-all">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Expiring Soon</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-accent/20 flex items-center justify-center">
                            <Clock class="h-4 w-4 text-accent" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-foreground">{{ expiringPlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Expires within 30 days</p>
                    </CardContent>
                </Card>

                <!-- Inactive Members -->
                <Card class="relative overflow-hidden border-l-4 border-l-destructive shadow-sm hover:shadow-md transition-all">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-muted-foreground">Inactive</CardTitle>
                        <div class="h-8 w-8 rounded-full bg-destructive/10 flex items-center justify-center">
                            <UserX class="h-4 w-4 text-destructive" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-foreground">{{ inactivePlayers }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Expired or suspended</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 md:grid-cols-7">
                <!-- Recent Registrations -->
                <Card class="md:col-span-4 lg:col-span-5 shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Feed</CardTitle>
                            <CardDescription>
                                Latest athletes added and recent membership status changes.
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="route('admin.players.index')">
                                View All <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Athlete</TableHead>
                                    <TableHead class="hidden sm:table-cell">Details</TableHead>
                                    <TableHead class="hidden md:table-cell">Membership</TableHead>
                                    <TableHead class="text-right">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="player in recentPlayers" :key="player.id">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-9 w-9 border border-border">
                                                <AvatarFallback :class="player.gender === 'Female' ? 'bg-primary/10 text-primary' : 'bg-secondary/10 text-secondary'">
                                                    {{ getInitials(player.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ player.name }}</span>
                                                <span class="text-xs text-muted-foreground sm:hidden">{{ player.club_location }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden sm:table-cell">
                                        <div class="flex flex-col text-sm">
                                            <span>{{ player.gender }}, {{ player.age }} yrs</span>
                                            <span class="text-xs text-muted-foreground">{{ player.club_location }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="hidden md:table-cell">
                                        <div class="flex flex-col text-sm">
                                            <span class="text-xs text-muted-foreground">Start: {{ player.membership_start }}</span>
                                            <span class="text-xs text-muted-foreground">End: {{ player.membership_end }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Badge 
                                            :variant="player.status === 'active' ? 'default' : (player.status === 'expiring' ? 'secondary' : 'outline')"
                                            :class="{
                                                'w-20 justify-center': true,
                                                'bg-primary/15 text-primary hover:bg-primary/25 border-primary/20': player.status === 'active',
                                                'bg-accent/15 text-accent hover:bg-accent/25 border-accent/20': player.status === 'expiring',
                                                'bg-destructive/15 text-red-500 hover:bg-destructive/25 border-destructive/20 font-semibold': player.status === 'inactive'
                                            }"
                                        >
                                            {{ player.status.charAt(0).toUpperCase() + player.status.slice(1) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="recentPlayers.length === 0">
                                    <TableCell colspan="4" class="text-center py-8 text-muted-foreground">
                                        No recent registrations found.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <!-- Quick Actions / Mini Stats -->
                <div class="md:col-span-3 lg:col-span-2 flex flex-col gap-6 h-full">
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4">
                            <Button class="w-full justify-start" as-child>
                                <Link :href="route('admin.players.index')">
                                    <Users class="mr-2 h-4 w-4" />
                                    Register New Athlete
                                </Link>
                            </Button>
                            <Button variant="outline" class="w-full justify-start" as-child>
                                <Link :href="route('admin.tournaments.index')">
                                    <Trophy class="mr-2 h-4 w-4" />
                                    Manage Tournaments
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm flex-1">
                        <CardHeader>
                            <CardTitle>Upcoming Events</CardTitle>
                            <CardDescription>Next 30 days</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-center py-4">
                                <Calendar class="h-12 w-12 opacity-80 text-muted-foreground" />
                            </div>
                            <p class="text-center text-sm text-muted-foreground">
                                No tournaments scheduled for the upcoming month.
                            </p>
                            <Button variant="secondary" class="w-full mt-4 font-semibold" as-child>
                                <Link :href="route('admin.tournaments.index')">
                                    Schedule Event
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
