<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { ref, computed, reactive, watch } from 'vue'
import { type BreadcrumbItem } from '@/types';

/* ================= TYPES ================= */
interface Player {
    id: number
    full_name: string
    gender: 'male' | 'female'
    club: string
    age_category_id: number | null
    age_category: string
    age: number
}

interface WeightCategory {
    id: number
    gender: 'male' | 'female'
    age_category_id: number
    name: string
    min_weight: number
    max_weight: number
}

interface Registration {
    player_id: number
    weigh_in_weight: number | null
    weight_category_id: number | null
}

/* ================= PROPS ================= */
const props = defineProps<{
    players: Player[]
    weightCategories: WeightCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: 'Create a Tournament', href: '' },
];

/* ================= FORM ================= */
const form = useForm({
    name: '',
    tournament_date: '',
    status: 'draft',
    registrations: [] as Registration[]
})

/* ================= LOCAL STATE ================= */
// Store weights for all players (key: player_id)
const playerWeights = reactive<Record<number, number | null>>({})

// Derived categories based on weight (key: player_id)
const playerCategories = reactive<Record<number, WeightCategory | null>>({})

/* ================= SEARCH ================= */
const search = ref('')
const genderFilter = ref<'all' | 'male' | 'female'>('all')
const categoryFilter = ref<string>('all')

const uniqueCategories = computed(() => {
    // Get unique categories and filter out any null/empty ones if necessary
    const categories = new Set(props.players.map(p => p.age_category).filter(Boolean))
    return Array.from(categories).sort()
})

const filteredPlayers = computed(() => {
    return props.players.filter(p => {
        const playerGender = String(p.gender).toLowerCase()
        const matchesGender = genderFilter.value === 'all' || playerGender === genderFilter.value
        
        const matchesCategory = categoryFilter.value === 'all' || p.age_category === categoryFilter.value

        const matchesSearch = !search.value ||
            p.full_name.toLowerCase().includes(search.value.toLowerCase()) ||
            p.club.toLowerCase().includes(search.value.toLowerCase())

        return matchesGender && matchesCategory && matchesSearch
    })
})

/* ================= LOGIC ================= */
const isSelected = (playerId: number) =>
    form.registrations.some(r => r.player_id === playerId)

const getCalculatedCategory = (player: Player, weight: number | null): WeightCategory | null => {
    if (!weight || weight <= 0 || !player.age_category_id) return null

    return props.weightCategories.find(w =>
        w.gender.toLowerCase() === player.gender.toLowerCase() &&
        w.age_category_id === player.age_category_id &&
        weight > w.min_weight &&
        weight <= w.max_weight
    ) ?? null
}

// Check eligibility: Must have Age Category AND Weight Category
const isEligible = (player: Player) => {
    return !!player.age_category_id && !!playerCategories[player.id]
}

const updateWeight = (player: Player) => {
    const weight = playerWeights[player.id] ?? null
    const category = getCalculatedCategory(player, weight)

    // Update local category state
    playerCategories[player.id] = category

    // If player is already selected, update their registration entry
    const index = form.registrations.findIndex(r => r.player_id === player.id)
    if (index !== -1) {
        if (category) {
            form.registrations[index].weigh_in_weight = weight
            form.registrations[index].weight_category_id = category.id
        } else {
            // If weight becomes invalid/cleared, remove them from selection?
            // Or just keep them but with nulls (which might fail validation or just be incomplete)
            // Let's remove them to enforce eligibility
            form.registrations.splice(index, 1)
        }
    }
}

const togglePlayer = (player: Player) => {
    if (!isEligible(player)) return

    const index = form.registrations.findIndex(r => r.player_id === player.id)
    if (index !== -1) {
        form.registrations.splice(index, 1)
    } else {
        const weight = playerWeights[player.id]
        const category = playerCategories[player.id]

        if (weight && category) {
            form.registrations.push({
                player_id: player.id,
                weigh_in_weight: weight,
                weight_category_id: category.id
            })
        }
    }
}

/* ================= SUBMIT ================= */
const totalRegistered = computed(() => form.registrations.length)
const submit = () => form.post(route('admin.tournaments.store'))
</script>

<template>
    <Head title="Create Tournament" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-8">

            <!-- Tournament Info -->
            <div class="space-y-4 max-w-xl">
                <h1 class="text-2xl font-bold">Create Tournament</h1>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Tournament Name</label>
                    <input v-model="form.name" placeholder="Tournament Name" class="w-full border bg-background rounded-lg p-2" />
                </div>
                
                <div class="space-y-2">
                    <label class="text-sm font-medium">Date</label>
                    <input type="date" v-model="form.tournament_date" class="w-full border bg-background rounded-lg p-2" />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">Status</label>
                    <select v-model="form.status" class="w-full border bg-background rounded-lg p-2">
                        <option value="draft">Draft</option>
                        <option value="open">Open</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>

            <!-- Players -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Register Players</h2>
                    <span class="text-sm text-muted-foreground">Total Registered: {{ totalRegistered }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <input v-model="search" placeholder="Search player by name or club..." class="border bg-background rounded-lg p-2 w-80" />
                    
                    <select v-model="categoryFilter" class="border bg-background rounded-lg p-2 text-sm">
                        <option value="all">All Categories</option>
                        <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                            {{ cat }}
                        </option>
                    </select>

                    <div class="flex gap-2">
                        <button type="button" class="px-3 py-1 rounded border text-sm transition-colors" :class="genderFilter==='all' ? 'bg-slate-900 text-white border-slate-900' : 'bg-background hover:bg-muted'" @click="genderFilter='all'">All</button>
                        <button type="button" class="px-3 py-1 rounded border text-sm transition-colors" :class="genderFilter==='male' ? 'bg-blue-600 text-white border-blue-600' : 'bg-background hover:bg-muted'" @click="genderFilter='male'">Male</button>
                        <button type="button" class="px-3 py-1 rounded border text-sm transition-colors" :class="genderFilter==='female' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-background hover:bg-muted'" @click="genderFilter='female'">Female</button>
                    </div>
                </div>

                <div class="border rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="p-3 w-12 text-center">Select</th>
                                <th class="p-3 text-left">Player</th>
                                <th class="p-3 text-center">Club</th>
                                <th class="p-3 text-center">Weigh-in (kg)</th>
                                <th class="p-3 text-center">Category</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="player in filteredPlayers"
                                :key="player.id"
                                class="border-t transition-colors"
                                :class="{
                                    'bg-green-500/10 hover:bg-green-500/20': isEligible(player),
                                    'bg-yellow-500/10 hover:bg-yellow-500/20': !isEligible(player) && playerWeights[player.id],
                                    'hover:bg-muted/50': !isEligible(player) && !playerWeights[player.id]
                                }"
                            >
                                <td class="p-3 text-center">
                                    <input
                                        type="checkbox"
                                        class="w-4 h-4 rounded border-gray-300"
                                        :checked="isSelected(player.id)"
                                        :disabled="!isEligible(player)"
                                        @change="togglePlayer(player)"
                                    />
                                </td>
                                <td class="p-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <span>{{ player.full_name }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded text-xs font-medium uppercase"
                                            :class="String(player.gender).toLowerCase()==='male' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                                        >
                                            {{ player.gender }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 text-center">{{ player.club }}</td>
                                <td class="p-3 text-center">
                                    <input 
                                        type="number" 
                                        v-model.number="playerWeights[player.id]"
                                        @input="updateWeight(player)"
                                        class="w-24 border rounded px-2 py-1 text-center"
                                        placeholder="kg"
                                        step="0.01"
                                    />
                                </td>
                                <td class="p-3 text-center">
                                    <div v-if="playerCategories[player.id]" class="flex flex-col items-center">
                                        <span class="font-semibold">{{ player.age_category }}</span>
                                        <span class="text-xs text-muted-foreground">{{ playerCategories[player.id]?.name }}</span>
                                    </div>
                                    <div v-else class="text-muted-foreground text-xs">
                                        {{ player.age_category }}
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <span
                                        v-if="isEligible(player)"
                                        class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700"
                                    >
                                        Eligible
                                    </span>
                                    <span
                                        v-else-if="!player.age_category_id"
                                        class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700"
                                    >
                                        Age Invalid
                                    </span>
                                    <span
                                        v-else-if="playerWeights[player.id]"
                                        class="px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-700"
                                    >
                                        Invalid Weight
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-1 rounded text-xs font-medium bg-amber-100 text-amber-700"
                                    >
                                        Need Weigh-in
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Button @click="submit" :disabled="form.processing || form.registrations.length === 0">
                Create Tournament
            </Button>

        </div>
    </AppLayout>
</template>
