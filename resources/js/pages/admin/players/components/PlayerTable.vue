<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Edit, Eye, UserCheck, UserX } from 'lucide-vue-next';

interface Player {
    id: number;
    full_name: string;
    gender: string;
    age: number;
    club: string;
    address: string;
    membership_expires_at: string;
    status: string;
}

defineProps<{
    players: {
        data: Player[];
        links: any[];
    };
}>();
function renewMembership(playerId: number) {
    if (confirm('Renew this membership for another year?')) {
        router.post(route('admin.players.renew', playerId));
    }
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>All Players</CardTitle>
            <CardDescription>A list of all players in the system. Players are active for 1 year from registration or renewal.</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full text-sm text-left caption-bottom">
                    <thead class="[&_tr]:border-b">
                        <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Name</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground text-center">Gender</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground text-center">Age</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Club</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Address</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground">Expiry Date</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground text-center">Status</th>
                            <th class="h-12 px-4 align-middle font-medium text-muted-foreground text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-for="player in players.data" :key="player.id" class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                            <td class="p-4 align-middle font-medium">{{ player.full_name }}</td>
                            <td class="p-4 align-middle text-center">
                                <Badge variant="secondary" class="rounded-full px-3">{{ player.gender }}</Badge>
                            </td>
                            <td class="p-4 align-middle text-center">{{ player.age }} yrs</td>
                            <td class="p-4 align-middle">{{ player.club }}</td>
                            <td class="p-4 align-middle">{{ player.address }}</td>
                            <td class="p-4 align-middle">{{ player.membership_expires_at }}</td>
                            <td class="p-4 align-middle text-center">
                                <div :class="{
                            'bg-red-500/20 text-red-500': player.status === 'inactive',
                            'bg-green-500/20 text-green-500': player.status === 'active'
                        }" class="inline-flex items-center justify-center rounded-md p-1">
                          <UserX v-if="player.status === 'inactive'" class="h-4 w-4" />
                          <UserCheck v-if="player.status === 'active'" class="h-4 w-4" />
                        </div>
                            </td>
                            <td class="p-4 align-middle text-center flex justify-center gap-2">                              
                                <!-- Edit -->
                                <Link :href="route('admin.players.edit', player.id)">
                                    <Button variant="outline" size="sm" class="h-8">
                                        <Edit class="mr-2 h-3 w-3" />
                                        Edit
                                    </Button>
                                </Link>

                                <!-- View -->
                                <Link :href="route('admin.players.show', player.id)" v-if="route().has('admin.players.show')">
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                </Link>

                                <!-- Renew (ONLY if inactive) -->
                                <Button
                                    v-if="player.status === 'inactive'"
                                    size="sm"
                                    class="h-8 bg-green-600 hover:bg-green-700 text-white"
                                    @click="renewMembership(player.id)"
                                >
                                    Renew
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="players.data.length === 0">
                            <td colspan="8" class="p-4 text-center text-muted-foreground">
                                No players found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
