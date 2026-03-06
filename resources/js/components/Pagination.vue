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
                 class="px-3 py-2 text-sm font-medium text-muted-foreground/50 border border-transparent rounded-md cursor-not-allowed" 
                 v-html="link.label" />
            <Link v-else 
                  class="px-3 py-2 text-sm font-medium border rounded-md transition-all duration-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-background" 
                  :class="{ 
                      'bg-primary text-primary-foreground border-primary hover:bg-primary/90': link.active,
                      'bg-background text-foreground border-border hover:bg-muted hover:text-accent': !link.active 
                  }" 
                  :href="link.url" 
                  v-html="link.label" />
        </template>
    </div>
</template>
