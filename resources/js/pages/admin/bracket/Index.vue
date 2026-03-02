<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import AppLayout from '@/layouts/AppLayout.vue'
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { 
    Trophy, 
    Calendar, 
    Users, 
    Play, 
    Eye,
    ListTree
} from 'lucide-vue-next'

interface Tournament {
    id: number
    name: string
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count: number
}

const props = defineProps<{
    tournaments: Tournament[]
}>()

const generate = (tournamentId: number) => {
    router.post(route('admin.tournaments.brackets.generate', tournamentId))
}
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed': return 'default'
        case 'ongoing': return 'secondary'
        case 'open': return 'outline'
        default: return 'secondary'
    }
}
</script>

<template>
    <Head title="Generate Brackets" />

    <AppLayout>
        <div class="p-6 space-y-6">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                    <ListTree class="h-6 w-6 text-primary" />
                    Bracket Management
                </h1>
                <p class="text-muted-foreground">
                    Generate and manage tournament brackets. Rules: 2-5 players (Round Robin), 6+ players (Single Elimination).
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Tournaments</CardTitle>
                    <CardDescription>Select a tournament to manage its brackets.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tournament</TableHead>
                                <TableHead class="text-center">Date</TableHead>
                                <TableHead class="text-center">Status</TableHead>
                                <TableHead class="text-center">Registrations</TableHead>
                                <TableHead class="text-center">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="tournament in props.tournaments" :key="tournament.id">
                                <TableCell class="font-medium">
                                    <div class="flex items-center gap-2">
                                        <Trophy class="h-4 w-4 text-amber-500" />
                                        {{ tournament.name }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2 text-muted-foreground">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(tournament.tournament_date) }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge :variant="getStatusVariant(tournament.status)" class="capitalize">
                                        {{ tournament.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        {{ tournament.registrations_count }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                            v-if="tournament.registrations_count >= 2"
                                            size="sm"
                                            @click="generate(tournament.id)"
                                        >
                                            <Play class="h-4 w-4 mr-1" />
                                            Generate
                                        </Button>
                                        <Button
                                            v-else
                                            size="sm"
                                            variant="secondary"
                                            disabled
                                            title="Need at least 2 players"
                                        >
                                            <Play class="h-4 w-4 mr-1" />
                                            Generate
                                        </Button>

                                        <Button as-child variant="outline" size="sm">
                                            <Link :href="route('admin.tournaments.brackets.show', tournament.id)">
                                                <Eye class="h-4 w-4 mr-1" />
                                                View
                                            </Link>
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="props.tournaments.length === 0">
                                <TableCell colspan="5" class="h-24 text-center text-muted-foreground">
                                    No tournaments found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
