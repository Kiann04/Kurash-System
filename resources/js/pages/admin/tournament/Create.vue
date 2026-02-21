<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { ref, computed } from 'vue'

/* ================= TYPES ================= */
interface Player {
    id: number
    full_name: string
    gender: 'male' | 'female'
    club: string
    age_category_id: number | null
    age_category: string
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

/* ================= FORM ================= */
const form = useForm({
    name: '',
    tournament_date: '',
    status: 'draft',
    registrations: [] as Registration[]
})

/* ================= SEARCH ================= */
const search = ref('')
const filteredPlayers = computed(() => {
    if (!search.value) return props.players
    return props.players.filter(p =>
        p.full_name.toLowerCase().includes(search.value.toLowerCase()) ||
        p.club.toLowerCase().includes(search.value.toLowerCase())
    )
})

/* ================= REGISTRATION ================= */
const isSelected = (playerId: number) =>
    form.registrations.some(r => r.player_id === playerId)

const isEligible = (player: Player) => !!player.age_category_id

const togglePlayer = (player: Player) => {
    if (!isEligible(player)) return

    const index = form.registrations.findIndex(r => r.player_id === player.id)
    if (index !== -1) form.registrations.splice(index, 1)
    else form.registrations.push({
        player_id: player.id,
        weigh_in_weight: null,
        weight_category_id: null
    })
}

const getRegistration = (playerId: number) =>
    form.registrations.find(r => r.player_id === playerId)!

/* ================= AUTO WEIGHT MATCH ================= */
const autoAssignWeight = (player: Player) => {
    const registration = getRegistration(player.id)
    const weight = registration.weigh_in_weight

    if (!weight || weight <= 0) {
        registration.weight_category_id = null
        return
    }

    const matched = props.weightCategories.find(w =>
        w.gender === player.gender &&
        w.age_category_id === player.age_category_id &&
        weight > w.min_weight &&
        weight <= w.max_weight
    )

    registration.weight_category_id = matched ? matched.id : null
}

/* ================= TOTAL ================= */
const totalRegistered = computed(() => form.registrations.length)

/* ================= SUBMIT ================= */
const submit = () => form.post(route('admin.tournaments.store'))
</script>

<template>
<Head title="Create Tournament" />
<AppLayout>
<div class="p-6 space-y-8">

    <!-- Tournament Info -->
    <div class="space-y-4 max-w-xl">
        <h1 class="text-2xl font-bold">Create Tournament</h1>

        <input v-model="form.name" placeholder="Tournament Name" class="w-full border rounded-lg p-2" />
        <input type="date" v-model="form.tournament_date" class="w-full border rounded-lg p-2" />
        <select v-model="form.status" class="w-full border rounded-lg p-2">
            <option value="draft">Draft</option>
            <option value="open">Open</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
        </select>
    </div>

    <!-- Players -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Register Players</h2>
            <span class="text-sm text-muted-foreground">Total Registered: {{ totalRegistered }}</span>
        </div>

        <input v-model="search" placeholder="Search player by name or club..." class="border rounded-lg p-2 mb-4 w-80" />

        <div class="border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="p-3">Select</th>
                        <th class="p-3 text-left">Player</th>
                        <th class="p-3">Club</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Weigh-in (kg)</th>
                    
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="player in filteredPlayers" :key="player.id" class="border-t">
                        <td class="p-3 text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(player.id)"
                                :disabled="!isEligible(player)"
                                @change="togglePlayer(player)"
                            />
                        </td>
                        <td class="p-3 font-medium">{{ player.full_name }}</td>
                        <td class="p-3 text-center">{{ player.club }}</td>
                        <td class="p-3 text-center">{{ player.age_category }}</td>
                        <td class="p-3">
                            <input
                                v-if="isSelected(player.id)"
                                type="number"
                                step="0.01"
                                v-model="getRegistration(player.id).weigh_in_weight"
                                @input="autoAssignWeight(player)"
                                class="border rounded p-1 w-24"
                                placeholder="kg"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Button @click="submit" :disabled="form.processing">Save Tournament</Button>

</div>
</AppLayout>
</template>
