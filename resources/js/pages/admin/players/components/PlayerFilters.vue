<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Search, Filter, X } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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
    <div class="flex items-center gap-2">
        <div class="relative w-full md:w-64">
            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
                :model-value="search"
                @update:model-value="$emit('update:search', $event as string)"
                placeholder="Search by name, club..."
                class="pl-9 h-9 bg-background border-slate-200"
            />
        </div>
        
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="sm" class="h-9 gap-2 border-slate-200">
                    <Filter class="h-3.5 w-3.5 text-muted-foreground" />
                    <span class="hidden sm:inline-block">Gender</span>
                    <span v-if="gender !== 'all'" class="ml-1 rounded-sm bg-primary/10 px-1 font-normal text-primary">
                        {{ gender }}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
                <DropdownMenuLabel>Filter by Gender</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuRadioGroup :model-value="gender" @update:model-value="$emit('update:gender', $event)">
                    <DropdownMenuRadioItem value="all">
                        All Genders
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="Male">
                        Male
                    </DropdownMenuRadioItem>
                    <DropdownMenuRadioItem value="Female">
                        Female
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
                <DropdownMenuSeparator v-if="gender !== 'all'" />
                <DropdownMenuItem v-if="gender !== 'all'" @click="$emit('update:gender', 'all')" class="justify-center text-center">
                    <Button variant="ghost" size="sm" class="h-auto w-full p-0 text-xs text-muted-foreground">
                        Clear Filters
                    </Button>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <Button 
            v-if="search || gender !== 'all'"
            variant="ghost" 
            size="icon" 
            class="h-9 w-9 text-muted-foreground hover:text-foreground"
            @click="$emit('update:search', ''); $emit('update:gender', 'all')"
            title="Reset filters"
        >
            <X class="h-4 w-4" />
        </Button>
    </div>
</template>
