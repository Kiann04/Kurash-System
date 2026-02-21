<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ref, computed, PropType } from 'vue'

interface Player {
    id: number
    full_name: string
    gender: 'male' | 'female'
    club?: string
    age_category: string
    weigh_in_weight?: number | null
    weight_category_id?: number | null
}

interface WeightCategory {
    id: number
    gender: 'male' | 'female'
    name: string
}

interface Tournament {
    id: number
    name: string
    tournament_date: string
    status: string
}

const props = defineProps<{
    tournament: Tournament
    players: Player[]
    weightCategories: WeightCategory[]
}>()

/* ================= REACTIVE STATES ================= */
const selectedGender = ref<string>('male')
const selectedAge = ref<string>('all')
const search = ref('')

// Pagination
const currentPage = ref(1)
const perPage = 20

/* ================= FILTERS ================= */
const filteredPlayers = computed(() => {
    let list = props.players

    // Gender filter
    list = list.filter(
        p => p.gender?.toLowerCase() === selectedGender.value.toLowerCase()
    )

    // Age category filter
    if (selectedAge.value !== 'all') {
        list = list.filter(p => p.age_category === selectedAge.value)
    }

    // Search filter
    if (search.value) {
        const query = search.value.toLowerCase()
        list = list.filter(
            p =>
                p.full_name.toLowerCase().includes(query) ||
                (p.club?.toLowerCase().includes(query) ?? false)
        )
    }

    return list
})

// Paginated slice
const paginatedPlayers = computed(() => {
    const start = (currentPage.value - 1) * perPage
    return filteredPlayers.value.slice(start, start + perPage)
})

const totalPages = computed(() =>
    Math.ceil(filteredPlayers.value.length / perPage)
)

/* ================= WEIGHT CLASS ================= */
const getWeightClass = (player: Player) => {
    if (!player.weigh_in_weight) return '-'
    const matched = props.weightCategories.find(w => w.id === player.weight_category_id)
    return matched ? matched.name : '-'
}

/* ================= AGE CATEGORIES ================= */
const ageCategories = computed(() => {
    const cats = new Set(props.players.map(p => p.age_category))
    return Array.from(cats).sort()
})
</script>

<template>
<Head :title="`Tournament: ${props.tournament.name}`" />

<AppLayout>
<div class="p-6 space-y-6">

    <h1 class="text-2xl font-bold">{{ props.tournament.name }}</h1>
    <p class="text-sm text-muted-foreground">
        Date: {{ props.tournament.tournament_date }} | Status: {{ props.tournament.status }}
    </p>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 mt-4 items-center">
        <!-- Gender Tabs -->
        <div class="flex space-x-2">
            <button
                @click="selectedGender = 'male'"
                :class="selectedGender==='male' ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                class="px-3 py-1 rounded">Male</button>
            <button
                @click="selectedGender = 'female'"
                :class="selectedGender==='female' ? 'bg-pink-500 text-white' : 'bg-gray-200'"
                class="px-3 py-1 rounded">Female</button>
        </div>

        <!-- Age Category -->
        <select v-model="selectedAge" class="border rounded p-1">
            <option value="all">All Ages</option>
            <option v-for="age in ageCategories" :key="age" :value="age">{{ age }}</option>
        </select>

        <!-- Search -->
        <input v-model="search" type="text" placeholder="Search name/club" class="border rounded p-1" />
    </div>

    <!-- Table -->
    <div class="mt-6 border rounded-lg overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Player</th>
                    <th class="p-3">Club</th>
                    <th class="p-3">Age Category</th>
                    <th class="p-3">Weigh-in (kg)</th>
                    <th class="p-3">Weight Class</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="player in paginatedPlayers" :key="player.id" class="border-t">
                    <td class="p-3 font-medium">{{ player.full_name }}</td>
                    <td class="p-3 text-center">{{ player.club || '-' }}</td>
                    <td class="p-3 text-center">{{ player.age_category }}</td>
                    <td class="p-3 text-center">{{ player.weigh_in_weight ?? '-' }}</td>
                    <td class="p-3 text-center">{{ getWeightClass(player) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="flex justify-between mt-4 items-center">
        <button
            :disabled="currentPage===1"
            @click="currentPage--"
            class="px-3 py-1 border rounded disabled:opacity-50">
            Previous
        </button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button
            :disabled="currentPage===totalPages"
            @click="currentPage++"
            class="px-3 py-1 border rounded disabled:opacity-50">
            Next
        </button>
    </div>

</div>
</AppLayout>
</template>