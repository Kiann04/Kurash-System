<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PlayerForm from './components/PlayerForm.vue';
import { route } from 'ziggy-js';

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
    };
}>();

const title = 'Edit Player';

const breadcrumbs = [
  { title: 'Players', href: route('admin.players.index') },
  { title: title },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-6">{{ title }}</h1>
      <PlayerForm :player="props.player" />
    </div>
  </AppLayout>
</template>
