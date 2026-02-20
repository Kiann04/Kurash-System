<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PlayerForm from './components/PlayerForm.vue';
import { route } from 'ziggy-js';

const title = 'Create Player';

const breadcrumbs = [
  { title: 'Players', href: route('admin.players.index') },
  { title: title },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <h1 class="text-2xl font-bold mb-6">{{ title }}</h1>
      <PlayerForm />
    </div>
  </AppLayout>
</template>
