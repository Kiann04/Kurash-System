<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  player: Object // Optional for edit
});

const form = useForm({
  full_name: props.player?.full_name || '',
  gender: props.player?.gender || '',
  age: props.player?.age || '',
  weight: props.player?.weight || '',
});

function submit() {
  if (props.player) {
    form.put(`/admin/players/${props.player.id}`);
  } else {
    form.post('/admin/players');
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <div>
      <label>Full Name</label>
      <input v-model="form.full_name" type="text" />
      <span>{{ form.errors.full_name }}</span>
    </div>

    <div>
      <label>Gender</label>
      <select v-model="form.gender">
        <option value="">Select</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
      <span>{{ form.errors.gender }}</span>
    </div>

    <div>
      <label>Age</label>
      <input v-model="form.age" type="number" />
      <span>{{ form.errors.age }}</span>
    </div>

    <div>
      <label>Weight</label>
      <input v-model="form.weight" type="number" step="0.1" />
      <span>{{ form.errors.weight }}</span>
    </div>

    <button type="submit">{{ props.player ? 'Update' : 'Create' }}</button>
  </form>
</template>