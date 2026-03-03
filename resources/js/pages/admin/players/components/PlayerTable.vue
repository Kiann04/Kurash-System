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
    <Card class="border-none shadow-none dark:bg-transparent">
        <CardContent class="p-0">
            <div class="rounded-md border dark:border-slate-700">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50 hover:bg-slate-50 dark:bg-slate-800/50 dark:hover:bg-slate-800/50">
                            <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Player</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Details</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Club / Address</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium dark:text-slate-400">Membership</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-center dark:text-slate-400">Status</TableHead>
                            <TableHead class="h-12 px-4 align-middle font-medium text-right dark:text-slate-400">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="player in players.data" :key="player.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors border-b dark:border-slate-700">
                            <TableCell class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-9 w-9 border border-slate-200 dark:border-slate-700">
                                        <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.full_name}&background=random`" :alt="player.full_name" />
                                        <AvatarFallback class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ getInitials(player.full_name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-slate-900 dark:text-slate-100">{{ player.full_name }}</span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400">ID: #{{ player.id.toString().padStart(4, '0') }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex flex-col gap-1 text-xs">
                                    <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                                        <span class="font-medium">{{ player.age }}</span> years old
                                    </div>
                                    <Badge variant="outline" class="w-fit text-xs px-1.5 py-0 h-5 border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                        {{ player.gender }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2 text-slate-700 font-medium text-xs dark:text-slate-300">
                                        <Shield class="h-3 w-3 text-blue-500" />
                                        {{ player.club || '-' }}
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500 text-xs truncate max-w-50 dark:text-slate-400" :title="player.address">
                                        <MapPin class="h-3 w-3" />
                                        {{ player.address || '-' }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle">
                                <div class="flex items-center gap-2 text-slate-600 text-xs dark:text-slate-400">
                                    <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                    <span>{{ player.membership_expires_at }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="p-4 align-middle text-center">
                                <Badge :variant="player.status === 'active' ? 'default' : 'destructive'" 
                                    :class="[
                                        'capitalize shadow-none font-normal',
                                        player.status === 'active' ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50' : 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50'
                                    ]">
                                    {{ player.status }}
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
                                    <DropdownMenuContent align="end" class="dark:bg-slate-950 dark:border-slate-800">
                                        <DropdownMenuLabel class="dark:text-slate-200">Actions</DropdownMenuLabel>
                                        <Link :href="route('admin.players.edit', player.id)">
                                            <DropdownMenuItem class="dark:focus:bg-slate-800 dark:focus:text-slate-100">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit Player
                                            </DropdownMenuItem>
                                        </Link>
                                        <Link :href="route('admin.players.show', player.id)" v-if="route().has('admin.players.show')">
                                            <DropdownMenuItem class="dark:focus:bg-slate-800 dark:focus:text-slate-100">
                                                <Eye class="mr-2 h-4 w-4" />
                                                View Details
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuSeparator class="dark:bg-slate-800" />
                                        <DropdownMenuItem 
                                            v-if="player.status === 'inactive'"
                                            @click="renewMembership(player.id)"
                                            class="text-green-600 focus:text-green-700 focus:bg-green-50 dark:text-green-400 dark:focus:text-green-300 dark:focus:bg-green-900/20"
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
                                    <Shield class="h-8 w-8 text-slate-300 dark:text-slate-600" />
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
        <DialogContent class="sm:max-w-md dark:bg-slate-950 dark:border-slate-800">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 dark:text-slate-100">
                    <RefreshCw class="h-5 w-5 text-green-600 dark:text-green-500" />
                    Renew Membership
                </DialogTitle>
                <DialogDescription class="dark:text-slate-400">
                    Are you sure you want to renew this player's membership for another year?
                </DialogDescription>
            </DialogHeader>
            <div class="py-4" v-if="renewPlayerId">
                <div class="rounded-md border p-4 bg-muted/20 dark:bg-slate-900/50 dark:border-slate-800 flex items-center gap-3">
                    <Shield class="h-8 w-8 text-slate-400 dark:text-slate-500" />
                    <div class="flex flex-col">
                        <span class="font-medium dark:text-slate-200">
                            {{ players.data.find(p => p.id === renewPlayerId)?.full_name }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            ID: #{{ renewPlayerId.toString().padStart(4, '0') }}
                        </span>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isRenewDialogOpen = false" class="dark:border-slate-800 dark:text-slate-300">
                    Cancel
                </Button>
                <Button @click="submitRenewMembership" class="bg-green-600 hover:bg-green-700 text-white dark:bg-green-700 dark:hover:bg-green-600">
                    Renew Membership
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
