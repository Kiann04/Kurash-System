<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps<{
  player?: {
    id: number;
    full_name: string;
    gender: string;
    age: number;
    weight: number;
  };
}>();

const form = useForm({
  full_name: props.player?.full_name || '',
  gender: props.player?.gender || '',
  age: props.player?.age || '',
  weight: props.player?.weight || '',
});

function submit() {
  if (props.player) {
    form.put(route('admin.players.update', props.player.id));
  } else {
    form.post(route('admin.players.store'));
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md space-y-4">
    
    <div>
      <label class="block text-sm font-medium mb-1">Full Name</label>
      <input
        v-model="form.full_name"
        type="text"
        placeholder="Enter full name"
        class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
      />
      <p class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Gender</label>
      <select
        v-model="form.gender"
        class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
      >
        <option value="">Select</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
      </select>
      <p class="text-red-500 text-sm mt-1">{{ form.errors.gender }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Age</label>
      <input
        v-model="form.age"
        type="number"
        placeholder="Enter age"
        class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
      />
      <p class="text-red-500 text-sm mt-1">{{ form.errors.age }}</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Weight (kg)</label>
      <input
        v-model="form.weight"
        type="number"
        step="0.1"
        placeholder="Enter weight"
        class="w-full rounded-md border border-gray-300 dark:border-gray-600 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
      />
      <p class="text-red-500 text-sm mt-1">{{ form.errors.weight }}</p>
    </div>

    <button
      type="submit"
      class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-md shadow-md transition"
    >
      {{ props.player ? 'Update Player' : 'Create Player' }}
    </button>

  </form>
</template>