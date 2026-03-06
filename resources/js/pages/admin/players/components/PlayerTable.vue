<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { 
    MoreHorizontal, 
    Edit, 
    Eye, 
    RefreshCw, 
    MapPin, 
    Calendar,
    Shield
} from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Pagination from '@/components/Pagination.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface Player {
    id: number;
    full_name: string;
    gender: string;
    age: number;
    club: string;
    address: string;
    membership_expires_at: string;
    membership_start_date: string;
    status: string;
}

defineProps<{
    players: {
        data: Player[];
        links: any[];
    };
}>();


const isRenewDialogOpen = ref(false);
const renewPlayerId = ref<number | null>(null);

function getUiStatus(player: Player) {
    try {
        const now = new Date();
        now.setHours(0, 0, 0, 0); // Reset time to start of day for accurate date comparison
        const expiry = new Date(player.membership_expires_at);
        expiry.setHours(0, 0, 0, 0); // Reset time to start of day
        
        // Calculate difference in days
        const diffMs = expiry.getTime() - now.getTime();
        const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));
        
        if (player.status === 'inactive' || expiry.getTime() <= now.getTime()) {
            return 'inactive';
        }
        
        // If diffDays is between 1 and 30 (inclusive), it's expiring soon
        if (diffDays <= 30) {
            return 'expiring';
        }
        return 'active';
    } catch {
        return player.status;
    }
}

function renewMembership(playerId: number) {
    renewPlayerId.value = playerId;
    isRenewDialogOpen.value = true;
}

function submitRenewMembership() {
    if (renewPlayerId.value) {
        router.post(route('admin.players.renew', renewPlayerId.value), {}, {
            onFinish: () => {
                isRenewDialogOpen.value = false;
                renewPlayerId.value = null;
            }
        });
    }
}

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
    <Card class="border-none shadow-none bg-transparent">
        <CardContent class="p-0">
            <div class="rounded-md border border-border">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-muted/50 hover:bg-muted/50">
                            <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground w-64">Player</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground w-24">Age</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground w-24">Gender</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground w-48">Club / Address</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-muted-foreground w-48">Membership Date</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-center text-muted-foreground w-40">Membership Status</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-right text-muted-foreground w-20">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="player in players.data" :key="player.id" class="hover:bg-muted/50 transition-colors border-b border-border">
                            <TableCell class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-9 w-9 border border-border">
                                        <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.full_name}&background=random`" :alt="player.full_name" />
                                        <AvatarFallback :class="player.gender === 'Female' ? 'bg-primary/10 text-primary border-primary/20' : 'bg-secondary/10 text-secondary border-secondary/20'">
                                            {{ getInitials(player.full_name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-foreground">{{ player.full_name }}</span>
                                        <span class="text-xs text-muted-foreground">ID: #{{ player.id.toString().padStart(4, '0') }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex items-center gap-1 text-muted-foreground">
                                    <span class="font-medium text-foreground">{{ player.age }}</span> years old
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <Badge variant="outline" class="w-fit text-xs px-1.5 py-0 h-5 border-border text-muted-foreground">
                                    {{ player.gender }}
                                </Badge>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-foreground font-medium text-xs">
                                        <Shield class="h-3 w-3 text-primary" />
                                        {{ player.club || '-' }}
                                    </div>
                                    <div class="flex items-center gap-2 text-muted-foreground text-xs truncate max-w-50" :title="player.address">
                                        <MapPin class="h-3 w-3" />
                                        {{ player.address || '-' }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex items-center gap-2 text-muted-foreground text-xs">
                                    <Calendar class="h-3.5 w-3.5 text-muted-foreground" />
                                    <span>{{ player.membership_start_date }} - {{ player.membership_expires_at }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle text-center">
                                <Badge 
                                    :variant="getUiStatus(player) === 'active' ? 'default' : (getUiStatus(player) === 'expiring' ? 'secondary' : 'destructive')"
                                    :class="[
                                        'capitalize shadow-none font-normal border',
                                        getUiStatus(player) === 'active' && 'bg-primary/15 text-primary hover:bg-primary/25 border-primary/20',
                                        getUiStatus(player) === 'expiring' && 'bg-accent/15 text-accent-foreground hover:bg-accent/25 border-accent/20',
                                        getUiStatus(player) === 'inactive' && 'bg-muted text-muted-foreground hover:bg-muted/80 border-border'
                                    ]">
                                    {{ getUiStatus(player) === 'expiring' ? 'Expiring' : getUiStatus(player) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="p-4 align-middle text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" class="h-8 w-8 p-0">
                                            <span class="sr-only">Open menu</span>
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <Link :href="route('admin.players.edit', player.id)">
                                            <DropdownMenuItem>
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit Player
                                            </DropdownMenuItem>
                                        </Link>
                                        <Link :href="route('admin.players.show', player.id)" v-if="route().has('admin.players.show')">
                                            <DropdownMenuItem>
                                                <Eye class="mr-2 h-4 w-4" />
                                                View Details
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem 
                                            v-if="player.status === 'inactive'"
                                            @click="renewMembership(player.id)"
                                            class="text-primary focus:text-primary focus:bg-primary/10"
                                        >
                                            <RefreshCw class="mr-2 h-4 w-4" />
                                            Renew Membership
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="players.data.length === 0">
                            <TableCell colspan="6" class="p-8 text-center text-muted-foreground">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <Shield class="h-8 w-8 text-muted-foreground/50" />
                                    <p>No players found matching your criteria.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div class="mt-4">
                <Pagination :links="players.links" />
            </div>
        </CardContent>
    </Card>

    <Dialog :open="isRenewDialogOpen" @update:open="isRenewDialogOpen = $event">
        <DialogContent class="sm:max-w-md bg-card border-border">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-foreground">
                    <RefreshCw class="h-5 w-5 text-primary" />
                    Renew Membership
                </DialogTitle>
                <DialogDescription class="text-muted-foreground">
                    Are you sure you want to renew this player's membership for another year?
                </DialogDescription>
            </DialogHeader>
            <div class="py-4" v-if="renewPlayerId">
                <div class="rounded-md border p-4 bg-muted/20 border-border flex items-center gap-3">
                    <Shield class="h-8 w-8 text-muted-foreground" />
                    <div class="flex flex-col">
                        <span class="font-medium text-foreground">
                            {{ players.data.find(p => p.id === renewPlayerId)?.full_name }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            ID: #{{ renewPlayerId.toString().padStart(4, '0') }}
                        </span>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isRenewDialogOpen = false">
                    Cancel
                </Button>
                <Button @click="submitRenewMembership" class="bg-primary hover:bg-primary/90 text-primary-foreground">
                    Renew Membership
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
