<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-vue-next';

defineProps<{
    search: string;
    gender: string;
}>();

defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:gender', value: string): void;
}>();
</script>

<template>
    <div class="flex items-center gap-4">
        <div class="relative w-64">
            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
                :model-value="search"
                @update:model-value="$emit('update:search', $event as string)"
                placeholder="Search players..."
                class="pl-8"
            />
        </div>
        <div class="flex items-center gap-2 bg-muted/50 p-1 rounded-lg">
            <span class="text-xs font-medium px-2">GENDER:</span>
            <Button 
                variant="ghost" 
                size="sm" 
                class="h-7 text-xs"
                :class="{ 'bg-white shadow-sm dark:bg-gray-800 text-foreground': gender === 'all', 'text-muted-foreground': gender !== 'all' }"
                @click="$emit('update:gender', 'all')"
            >All</Button>
            <Button 
                variant="ghost" 
                size="sm" 
                class="h-7 text-xs"
                :class="{ 'bg-white shadow-sm dark:bg-gray-800 text-foreground': gender === 'Male', 'text-muted-foreground': gender !== 'Male' }"
                @click="$emit('update:gender', 'Male')"
            >Male</Button>
            <Button 
                variant="ghost" 
                size="sm" 
                class="h-7 text-xs"
                :class="{ 'bg-white shadow-sm dark:bg-gray-800 text-foreground': gender === 'Female', 'text-muted-foreground': gender !== 'Female' }"
                @click="$emit('update:gender', 'Female')"
            >Female</Button>
        </div>
    </div>
</template>
