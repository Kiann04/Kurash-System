<script setup lang="ts">
/**
 * Create Tournament Page
 * 
 * Allows administrators to create a new tournament event.
 * Features:
 * - Tournament details entry
 * - Initial category setup
 * - Player registration and assignment
 * - Bulk import functionality
 */
import { Head } from '@inertiajs/vue3'
import { Save, Loader2 } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Toaster } from '@/components/ui/sonner'
import { useTournamentForm } from '@/composables/tournament/useTournamentForm'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import type { Player, TournamentWeightCategory, Registration } from '@/types/tournament'

// Components
import ImportSection from './components/ImportSection.vue'
import RegistrationManager from './components/RegistrationManager.vue'
import RegistrationSummary from './components/RegistrationSummary.vue'
import TournamentForm from './components/TournamentForm.vue'
import WeightCategoryManager from './components/WeightCategoryManager.vue'

/**
 * Page props
 * @property players - List of all available players
 * @property tournamentWeightCategories - List of available weight category templates
 */
interface Props {
    players: Player[]
    tournamentWeightCategories: TournamentWeightCategory[]
    initial?: {
        name?: string
        location?: string | null
        tournament_date?: string
        status?: string
    }
}

const props = defineProps<Props>()

// Breadcrumb navigation configuration
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: 'Create Tournament', href: '' },
]

/**
 * Tournament Form Composable
 * Manages form state for creating a new tournament.
 */
const {
    form,
    weightCategories,
    selectedCategoryId,
    selectedCategory,
    addRegistrations,
    removeRegistration
} = useTournamentForm({
    weight_categories: props.tournamentWeightCategories,
    name: props.initial?.name ?? '',
    location: props.initial?.location ?? '',
    tournament_date: props.initial?.tournament_date ?? undefined,
    status: props.initial?.status ?? 'draft'
})

/**
 * Submits the new tournament to the server.
 */
const submit = () => {
    form.post(route('admin.tournaments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show success message or redirect handled by backend/Inertia
        },
    })
}

/**
 * Handles the import of registrations from an external source.
 * @param newRegistrations Array of registration objects to add
 */
const handleImport = (newRegistrations: Registration[]) => {
    addRegistrations(newRegistrations)
}

/**
 * Retrieves the CSRF token for secure requests.
 */
const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Tournament" />

        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto">
            <!-- Header Section: Title and Actions (Cancel/Create) -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        Create Tournament
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Set up a new tournament and register players.
                    </p>
                </div>
                <div class="flex items-center gap-2">
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
                        Create Tournament
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

                <!-- Middle Section: Category Selection & Registration Summary -->
                <div class="grid gap-6 lg:grid-cols-12">
                    <div class="lg:col-span-8 space-y-6">
                        <WeightCategoryManager
                            v-model="selectedCategoryId"
                            v-model:weightCategories="weightCategories"
                        />
                    </div>

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
