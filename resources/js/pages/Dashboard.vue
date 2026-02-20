<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes'; // Assuming this works as it was in the original file
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
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
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Stats Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">Total Players</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ totalPlayers }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">Active Players</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-500">{{ activePlayers }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium text-muted-foreground">Inactive Players</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-500">{{ inactivePlayers }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Players Table -->
            <Card class="flex-1">
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Recent Players</CardTitle>
                        <CardDescription>A list of recently registered Kurash players.</CardDescription>
                    </div>
                    <Button variant="outline" size="sm">View All</Button>
                </CardHeader>
                <CardContent>
                    <div class="relative w-full overflow-auto">
                        <table class="w-full text-sm text-left caption-bottom">
                            <thead class="[&_tr]:border-b">
                                <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Name</th>
                                    <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Age / Group</th>
                                    <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Club</th>
                                    <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Expiry Date</th>
                                    <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Status</th>
                                </tr>
                            </thead>
                            <tbody class="[&_tr:last-child]:border-0">
                                <tr v-for="player in recentPlayers" :key="player.id" class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                    <td class="p-4 align-middle">
                                        <div class="font-medium">{{ player.name }}</div>
                                        <div class="text-xs text-muted-foreground uppercase">{{ player.gender }}</div>
                                    </td>
                                    <td class="p-4 align-middle">{{ player.age }} yrs</td>
                                    <td class="p-4 align-middle">{{ player.club }}</td>
                                    <td class="p-4 align-middle">{{ player.expiry_date }}</td>
                                    <td class="p-4 align-middle">
                                        <span :class="{
                                            'text-green-500': player.status === 'active',
                                            'text-red-500': player.status === 'inactive'
                                        }" class="capitalize">
                                            {{ player.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="recentPlayers.length === 0">
                                    <td colspan="5" class="p-4 text-center text-muted-foreground">
                                        No players found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
