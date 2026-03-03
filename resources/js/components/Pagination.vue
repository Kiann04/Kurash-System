<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}>();
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-1 mt-6 justify-end">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null" 
                 class="px-3 py-2 text-sm font-medium text-slate-400 border border-transparent rounded-md cursor-not-allowed dark:text-slate-600" 
                 v-html="link.label" />
            <Link v-else 
                  class="px-3 py-2 text-sm font-medium border rounded-md transition-all duration-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900" 
                  :class="{ 
                      'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-700': link.active,
                      'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:text-indigo-600 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800 dark:hover:bg-slate-800 dark:hover:text-indigo-400': !link.active 
                  }" 
                  :href="link.url" 
                  v-html="link.label" />
        </template>
    </div>
</template>
