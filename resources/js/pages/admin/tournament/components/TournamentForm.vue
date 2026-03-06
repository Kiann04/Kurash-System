<script setup lang="ts">
import { useVModel } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { DatePicker } from '@/components/ui/date-picker'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

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
    <Card class="shadow-sm border-border bg-card text-card-foreground">
        <CardHeader class="border-b bg-muted/50 pb-4">
            <CardTitle class="text-base font-semibold text-foreground">Tournament Details</CardTitle>
            <CardDescription class="text-muted-foreground">Basic information about the tournament.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
            <!-- Tournament Name Input -->
            <div class="space-y-2">
                <Label for="name">Tournament Name</Label>
                <Input id="name" v-model="localName" placeholder="e.g. National Championship 2024" class="bg-background border-input" />
                <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
            </div>
            
            <!-- Location Input -->
            <div class="space-y-2">
                <Label for="location">Location</Label>
                <!-- Manually handling model-value update to ensure null/string compatibility -->
                <Input 
                    id="location" 
                    :model-value="localLocation ?? ''"
                    @update:model-value="localLocation = $event as string || null"
                    placeholder="e.g. City Sports Complex" 
                    class="bg-background border-input" 
                />
                <p v-if="errors.location" class="text-sm text-destructive">{{ errors.location }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Date Input -->
                <div class="space-y-2">
                    <Label for="date">Date</Label>
                    <DatePicker id="date" v-model="localDate" class="bg-background border-input" />
                    <p v-if="errors.tournament_date" class="text-sm text-destructive">{{ errors.tournament_date }}</p>
                </div>
                <!-- Status Dropdown -->
                <div class="space-y-2">
                    <Label for="status">Status</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="w-full justify-between bg-background border-input text-foreground capitalize font-normal">
                                {{ localStatus }}
                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="localStatus = 'draft'" class="capitalize">Draft</DropdownMenuItem>
                            <DropdownMenuItem @click="localStatus = 'open'" class="capitalize">Open</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <p v-if="errors.status" class="text-sm text-destructive">{{ errors.status }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
