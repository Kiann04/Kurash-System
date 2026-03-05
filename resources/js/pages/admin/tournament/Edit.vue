<script setup lang="ts">
/**
 * Edit Tournament Page
 * 
 * Allows administrators to update tournament details and manage player registrations.
 * Features:
 * - Tournament details form (name, location, date, status)
 * - Registration statistics dashboard
 * - Weight category management
 * - Player assignment to weight categories
 * - Registration summary sidebar
 */
import { Head } from '@inertiajs/vue3'
import { Save, Loader2, Trash2 } from 'lucide-vue-next'
import { computed } from 'vue'
import { route } from 'ziggy-js'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Toaster } from '@/components/ui/sonner'
import { useTournamentForm } from '@/composables/tournament/useTournamentForm'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import type { Player, TournamentWeightCategory, Registration, TournamentRegistration } from '@/types/tournament'

// Components
import ImportSection from './components/ImportSection.vue'
import RegistrationManager from './components/RegistrationManager.vue'
import RegistrationSummary from './components/RegistrationSummary.vue'
import TournamentForm from './components/TournamentForm.vue'
import WeightCategoryManager from './components/WeightCategoryManager.vue'

/**
 * Page props
 * @property tournament - The tournament data to edit
 * @property players - List of all available players for registration
 * @property tournamentWeightCategories - List of weight categories associated with this tournament
 */
interface Props {
    tournament: {
        id: number
        name: string
        location: string | null
        tournament_date: string
        status: string
        registrations: TournamentRegistration[]
    }
    players: Player[]
    tournamentWeightCategories: TournamentWeightCategory[]
}

const props = defineProps<Props>()

// Breadcrumb navigation configuration
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: props.tournament.name, href: route('admin.tournaments.show', props.tournament.id) },
    { title: 'Edit', href: '' },
]

/**
 * Tournament Form Composable
 * Manages the form state, validation, and submission logic.
 * Also handles the complex logic of mapping registrations to weight categories.
 */
const {
    form,
    weightCategories,
    selectedCategoryId,
    selectedCategory,
    addRegistrations,
    removeRegistration
} = useTournamentForm({
    name: props.tournament.name,
    location: props.tournament.location,
    tournament_date: props.tournament.tournament_date,
    status: props.tournament.status,
    registrations: props.tournament.registrations
        .filter((registration) => registration.weight_category_id !== null)
        .map((registration) => ({
            player_id: registration.player_id,
            tournament_weight_category_id: registration.weight_category_id as number,
        })),
    weight_categories: props.tournamentWeightCategories
})

import { Users, LayoutGrid, CheckCircle2, UserCheck } from 'lucide-vue-next'

/**
 * Computed Property: Stats
 * Generates real-time statistics for the tournament dashboard.
 * Updates automatically as registrations are added or removed.
 */
const stats = computed(() => {
    const totalEntries = form.registrations.length
    const uniquePlayers = new Set(form.registrations.map((r: Registration) => r.player_id)).size
    const categoriesUsed = new Set(form.registrations.map((r: Registration) => r.tournament_weight_category_id)).size
    
    return [
        {
            label: 'Total Entries',
            value: totalEntries,
            icon: Users,
        },
        {
            label: 'Unique Players',
            value: uniquePlayers,
            icon: UserCheck,
        },
        {
            label: 'Categories Used',
            value: categoriesUsed,
            icon: LayoutGrid,
        },
        {
            label: 'Selected Category',
            value: selectedCategory.value ? selectedCategory.value.name : '-',
            subValue: selectedCategory.value ? `${selectedCategory.value.gender} • ${selectedCategory.value.age_category_name}` : '',
            icon: CheckCircle2,
        },
    ]
})

/**
 * Submits the updated tournament data to the server.
 */
const submit = () => {
    form.put(route('admin.tournaments.update', props.tournament.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show success message or redirect handled by backend/Inertia
        },
    })
}

/**
 * Deletes the tournament and all associated data.
 * Redirects to the tournament index page on success.
 */
const deleteTournament = () => {
    form.delete(route('admin.tournaments.destroy', props.tournament.id), {
        onSuccess: () => {
            // Redirect happens automatically
        },
    })
}

/**
 * Handles the import of registrations from an external source (e.g., CSV).
 * @param newRegistrations Array of registration objects to add
 */
const handleImport = (newRegistrations: Registration[]) => {
    addRegistrations(newRegistrations)
}

/**
 * Retrieves the CSRF token for secure requests.
 * Useful for components that might need to make manual API calls.
 */
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Edit ${tournament.name}`" />

        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header Section: Title and Actions (Save/Delete) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        Edit Tournament
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Update tournament details and manage registrations.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Delete Confirmation Dialog -->
                     <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button variant="destructive" class="mr-2">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Delete
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent class="dark:bg-slate-950 dark:border-slate-800">
                            <AlertDialogHeader>
                                <AlertDialogTitle class="dark:text-slate-100">Are you sure?</AlertDialogTitle>
                                <AlertDialogDescription>
                                    This action cannot be undone. This will permanently delete the tournament
                                    and all associated data.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel class="dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">Cancel</AlertDialogCancel>
                                <AlertDialogAction @click="deleteTournament" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                                    Delete
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>

                    <Button
                        variant="outline"
                        :href="route('admin.tournaments.index')"
                        as="a"
                        class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700">
                        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        <Save v-else class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Top Section: Details Form & Import Tool -->
                <div class="grid gap-6 md:grid-cols-2">
                    <TournamentForm
                        v-model:name="form.name"
                        v-model:location="form.location"
                        v-model:tournamentDate="form.tournament_date"
                        v-model:status="form.status"
                        :errors="form.errors"
                    />

                    <ImportSection 
                        :csrf-token="getCsrfToken()"
                        @imported="handleImport"
                    />
                </div>

                <!-- Stats Section: Dashboard Overview -->
                <div>
                    <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-slate-100">Registration Manager</h2>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="stat in stats" :key="stat.label" class="dark:bg-slate-950 dark:border-slate-800">
                            <CardContent class="p-6 flex items-center justify-between space-y-0">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                        {{ stat.label }}
                                    </p>
                                    <div class="flex items-baseline gap-2">
                                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                            {{ stat.value }}
                                        </p>
                                        <p v-if="stat.subValue" class="text-xs text-slate-500">
                                            {{ stat.subValue }}
                                        </p>
                                    </div>
                                </div>
                                <component :is="stat.icon" class="h-4 w-4 text-slate-400" />
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Middle Section: Category Selection & Registration Summary -->
                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-8 space-y-6">
                        <!-- Weight Category Selection (Dropdown) -->
                        <WeightCategoryManager
                            v-model="selectedCategoryId"
                            v-model:weightCategories="weightCategories"
                        />
                    </div>

                    <!-- Registration Summary Sidebar -->
                    <div class="lg:col-span-4">
                        <RegistrationSummary
                            :registrations="form.registrations"
                            :players="players"
                            :weight-categories="weightCategories"
                            @remove="removeRegistration"
                        />
                    </div>
                </div>

                <!-- Bottom Section: Player Assignment Panel (Full Width) -->
                <div class="w-full">
                    <RegistrationManager
                        :players="players"
                        :selected-category="selectedCategory"
                        :weight-categories="weightCategories"
                        v-model:registrations="form.registrations"
                    />
                </div>
            </div>
        </div>
        <Toaster />
    </AppLayout>
</template>
