<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { route } from 'ziggy-js';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

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
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4 space-y-4">
      <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">{{ player.full_name }}</h1>
        <Link :href="route('admin.players.edit', player.id)">
            <Button>Edit Player</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
            <CardTitle>Player Details</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Gender</span>
                    <p>{{ player.gender }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Age</span>
                    <p>{{ player.age }} years</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Club</span>
                    <p>{{ player.club || '-' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Address</span>
                    <p>{{ player.address || '-' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Email</span>
                    <p>{{ player.email }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Status</span>
                    <p class="capitalize">{{ player.status }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Emergency Contact</span>
                    <p>{{ player.emergency_contact }} ({{ player.emergency_contact_number }})</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-muted-foreground">Membership Expiry</span>
                    <p>{{ player.membership_expires_at }}</p>
                </div>
            </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
