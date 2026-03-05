import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Registration, TournamentWeightCategory, Player } from '@/types/tournament'

/**
 * Composable for managing tournament form state and logic.
 * Handles initialization, category selection, and registration management.
 * 
 * @param initialData - Optional initial data for the form
 */
export function useTournamentForm(initialData: any = {}) {
    // Form State: Manages tournament details and registrations
    const form = useForm({
        name: initialData.name || '',
        location: initialData.location || '',
        tournament_date: initialData.tournament_date || new Date().toISOString().split('T')[0],
        status: initialData.status || 'draft',
        registrations: initialData.registrations || [],
    })

    // State: Available weight categories
    const weightCategories = ref<TournamentWeightCategory[]>(initialData.weight_categories || [])
    
    // State: Currently selected category for management
    const selectedCategory = ref<TournamentWeightCategory | null>(null)
    const selectedCategoryId = ref<number | null>(null)

    // Watcher: Syncs selectedCategory object when selectedCategoryId changes
    watch(selectedCategoryId, (newId) => {
        if (newId) {
            selectedCategory.value = weightCategories.value.find(c => c.id === newId) || null
        } else {
            selectedCategory.value = null
        }
    })

    // Action: Updates the selected category ID
    const updateSelectedCategory = (id: number | null) => {
        selectedCategoryId.value = id
        if (id) {
            selectedCategory.value = weightCategories.value.find(c => c.id === id) || null
        } else {
            selectedCategory.value = null
        }
    }

    // Action: Adds new registrations to the form, avoiding duplicates
    const addRegistrations = (newRegistrations: Registration[]) => {
        // Merge logic to avoid duplicates
        const existingIds = new Set(form.registrations.map((r: Registration) => `${r.player_id}-${r.tournament_weight_category_id}`))
        
        newRegistrations.forEach(reg => {
            const key = `${reg.player_id}-${reg.tournament_weight_category_id}`
            if (!existingIds.has(key)) {
                form.registrations.push(reg)
                existingIds.add(key)
            }
        })
    }

    // Action: Removes a registration by player ID and optional category ID
    const removeRegistration = (playerId: number, categoryId?: number) => {
        if (categoryId) {
            form.registrations = form.registrations.filter((r: Registration) => 
                !(r.player_id === playerId && r.tournament_weight_category_id === categoryId)
            )
        } else {
            form.registrations = form.registrations.filter((r: Registration) => r.player_id !== playerId)
        }
    }

    // Action: Replaces all registrations with a new list
    const updateRegistrations = (newRegistrations: Registration[]) => {
        form.registrations = newRegistrations
    }

    return {
        form,
        weightCategories,
        selectedCategoryId,
        selectedCategory,
        updateSelectedCategory,
        addRegistrations,
        removeRegistration,
        updateRegistrations
    }
}
