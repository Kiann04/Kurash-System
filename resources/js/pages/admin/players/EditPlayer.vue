<template>
  <div>
    <h2>Edit Player</h2>
    <PlayerForm :player="player" :isEdit="true" @submit="updatePlayer" />
  </div>
</template>

<script setup lang="ts">
import PlayerForm from './PlayerForm.vue';
import { reactive, defineProps } from 'vue';

const props = defineProps<{ player: any }>();
const player = reactive(props.player);

const updatePlayer = (data: any) => {
  console.log('Update player', data);
  // call API to update
};
</script>