<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ChevronDown } from 'lucide-vue-next'
import { useVModel } from '@vueuse/core'

interface Props {
    name: string
    location: string | null
    tournamentDate: string
    status: string
    errors?: Record<string, string>
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
})

const emit = defineEmits<{
    (e: 'update:name', value: string): void
    (e: 'update:location', value: string | null): void
    (e: 'update:tournamentDate', value: string): void
    (e: 'update:status', value: string): void
}>()

// Use v-model to bind props to local state and emit updates automatically
const localName = useVModel(props, 'name', emit)
const localLocation = useVModel(props, 'location', emit)
const localDate = useVModel(props, 'tournamentDate', emit)
const localStatus = useVModel(props, 'status', emit)
</script>

<template>
    <Card class="shadow-sm border-slate-200 dark:bg-slate-950 dark:border-slate-800">
        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 pb-4">
            <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">Tournament Details</CardTitle>
            <CardDescription>Basic information about the tournament.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
            <!-- Tournament Name Input -->
            <div class="space-y-2">
                <Label for="name" class="dark:text-slate-300">Tournament Name</Label>
                <Input id="name" v-model="localName" placeholder="e.g. National Championship 2024" class="dark:bg-slate-950 dark:border-slate-800" />
                <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
            </div>
            
            <!-- Location Input -->
            <div class="space-y-2">
                <Label for="location" class="dark:text-slate-300">Location</Label>
                <!-- Manually handling model-value update to ensure null/string compatibility -->
                <Input 
                    id="location" 
                    :model-value="localLocation ?? ''"
                    @update:model-value="localLocation = $event as string || null"
                    placeholder="e.g. City Sports Complex" 
                    class="dark:bg-slate-950 dark:border-slate-800" 
                />
                <p v-if="errors.location" class="text-sm text-destructive">{{ errors.location }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Date Input -->
                <div class="space-y-2">
                    <Label for="date" class="dark:text-slate-300">Date</Label>
                    <Input id="date" type="date" v-model="localDate" class="dark:bg-slate-950 dark:border-slate-800" />
                    <p v-if="errors.tournament_date" class="text-sm text-destructive">{{ errors.tournament_date }}</p>
                </div>
                <!-- Status Dropdown -->
                <div class="space-y-2">
                    <Label for="status" class="dark:text-slate-300">Status</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 capitalize font-normal">
                                {{ localStatus }}
                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="dark:bg-slate-950 dark:border-slate-800">
                            <DropdownMenuItem @click="localStatus = 'draft'" class="capitalize dark:text-slate-200 dark:focus:bg-slate-800">Draft</DropdownMenuItem>
                            <DropdownMenuItem @click="localStatus = 'open'" class="capitalize dark:text-slate-200 dark:focus:bg-slate-800">Open</DropdownMenuItem>
                            <DropdownMenuItem @click="localStatus = 'ongoing'" class="capitalize dark:text-slate-200 dark:focus:bg-slate-800">Ongoing</DropdownMenuItem>
                            <DropdownMenuItem @click="localStatus = 'completed'" class="capitalize dark:text-slate-200 dark:focus:bg-slate-800">Completed</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <p v-if="errors.status" class="text-sm text-destructive">{{ errors.status }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
