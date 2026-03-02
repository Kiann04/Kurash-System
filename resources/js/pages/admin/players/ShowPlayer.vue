<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { route } from 'ziggy-js';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Link } from '@inertiajs/vue3';
import { 
    User, 
    Calendar, 
    Users, 
    MapPin, 
    Mail, 
    Phone, 
    Edit,
    ShieldCheck,
    Clock,
    Activity
} from 'lucide-vue-next';

const props = defineProps<{
    player: {
        id: number;
        full_name: string;
        gender: string;
        birthday: string;
        club: string;
        address: string;
        email: string;
        emergency_contact: string;
        emergency_contact_number: string;
        registered_at: string;
        membership_expires_at: string;
        status: string;
        age: number;
    };
}>();

const breadcrumbs = [
  { title: 'Players', href: route('admin.players.index') },
  { title: props.player.full_name },
];

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
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-6 max-w-5xl mx-auto">
      
      <!-- Header Section -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-card p-6 rounded-xl border shadow-sm">
        <div class="flex items-center gap-4">
            <Avatar class="h-20 w-20 border-2 border-primary/10">
                <AvatarImage :src="`https://api.dicebear.com/7.x/initials/svg?seed=${player.full_name}`" />
                <AvatarFallback class="text-xl bg-primary/5 text-primary">{{ getInitials(player.full_name) }}</AvatarFallback>
            </Avatar>
            <div>
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    {{ player.full_name }}
                    <Badge :variant="player.status === 'active' ? 'default' : 'secondary'" class="capitalize">
                        {{ player.status }}
                    </Badge>
                </h1>
                <div class="flex flex-wrap gap-4 text-sm text-muted-foreground mt-1">
                    <span class="flex items-center gap-1">
                        <Users class="h-4 w-4" />
                        {{ player.club || 'No Club' }}
                    </span>
                    <span class="flex items-center gap-1">
                        <User class="h-4 w-4" />
                        {{ player.gender }} • {{ player.age }} years old
                    </span>
                </div>
            </div>
        </div>
        <Link :href="route('admin.players.edit', player.id)">
            <Button>
                <Edit class="mr-2 h-4 w-4" />
                Edit Player
            </Button>
        </Link>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <!-- Personal & Contact Info -->
        <Card class="h-full">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <User class="h-5 w-5 text-primary" />
                    Personal & Contact Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="grid gap-4">
                    <div class="flex items-start gap-3">
                        <Mail class="h-5 w-5 text-muted-foreground mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Email Address</p>
                            <p class="text-sm text-muted-foreground">{{ player.email || 'Not provided' }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <MapPin class="h-5 w-5 text-muted-foreground mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Address</p>
                            <p class="text-sm text-muted-foreground">{{ player.address || 'Not provided' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <Calendar class="h-5 w-5 text-muted-foreground mt-0.5" />
                        <div>
                            <p class="text-sm font-medium">Birthday</p>
                            <p class="text-sm text-muted-foreground">{{ player.birthday }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-muted/50 p-4 rounded-lg border">
                    <h4 class="font-medium text-sm mb-3 flex items-center gap-2">
                        <Phone class="h-4 w-4" />
                        Emergency Contact
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-muted-foreground">Name</p>
                            <p class="text-sm font-medium">{{ player.emergency_contact || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Phone</p>
                            <p class="text-sm font-medium">{{ player.emergency_contact_number || '-' }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Membership Status -->
        <Card class="h-full">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <ShieldCheck class="h-5 w-5 text-primary" />
                    Membership Status
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="grid gap-4">
                    <div class="flex items-center justify-between p-3 border rounded-lg bg-card">
                        <div class="flex items-center gap-3">
                            <Activity class="h-5 w-5 text-muted-foreground" />
                            <span class="text-sm font-medium">Current Status</span>
                        </div>
                        <Badge :variant="player.status === 'active' ? 'default' : 'secondary'" class="capitalize">
                            {{ player.status }}
                        </Badge>
                    </div>

                    <div class="flex items-center justify-between p-3 border rounded-lg bg-card">
                        <div class="flex items-center gap-3">
                            <Calendar class="h-5 w-5 text-muted-foreground" />
                            <span class="text-sm font-medium">Registration Date</span>
                        </div>
                        <span class="text-sm text-muted-foreground">{{ player.registered_at }}</span>
                    </div>

                    <div class="flex items-center justify-between p-3 border rounded-lg bg-card">
                        <div class="flex items-center gap-3">
                            <Clock class="h-5 w-5 text-muted-foreground" />
                            <span class="text-sm font-medium">Membership Expires</span>
                        </div>
                        <span class="text-sm font-medium" :class="new Date(player.membership_expires_at) < new Date() ? 'text-destructive' : 'text-primary'">
                            {{ player.membership_expires_at }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
