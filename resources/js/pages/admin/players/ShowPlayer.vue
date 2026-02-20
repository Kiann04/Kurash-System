<template>
  <div>
    <h2>Player Details</h2>
    <p><strong>Name:</strong> {{ player.full_name }}</p>
    <p><strong>Gender:</strong> {{ player.gender }}</p>
    <p><strong>Weight:</strong> {{ player.weight }}</p>
  </div>
</template>

<script setup lang="ts">
import { defineProps } from 'vue';

const props = defineProps<{ player: any }>();
const player = props.player;
</script>