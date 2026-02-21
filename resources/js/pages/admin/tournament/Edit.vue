<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { type BreadcrumbItem } from '@/types';
import { ref, computed } from 'vue'


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

const props = defineProps<{
    tournament: {
        id: number
        name: string
        tournament_date: string
        status: string
        registrations: Registration[]
    }
    players: Player[]
    weightCategories: WeightCategory[]
}>()
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: props.tournament.name, href: route('admin.tournaments.show', props.tournament.id) },
    { title: 'Edit', href: '' },
];

const form = useForm({
    name: props.tournament.name,
    tournament_date: props.tournament.tournament_date,
    status: props.tournament.status,
    registrations: props.tournament.registrations.map(r => ({
        player_id: r.player_id,
        weigh_in_weight: r.weigh_in_weight,
        weight_category_id: r.weight_category_id
    })) as Registration[]
})

const search = ref('')
const genderFilter = ref<'all' | 'male' | 'female'>('all')
const categoryFilter = ref<string>('all')

const uniqueCategories = computed(() => {
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

const isSelected = (playerId: number) => form.registrations.some(r => r.player_id === playerId)
const isEligible = (player: Player) => !!player.age_category_id
const activeWeighInPlayerId = ref<number | null>(null)
const draftWeight = ref<number | null>(null)

const activeWeighInPlayer = computed(() =>
    props.players.find(p => p.id === activeWeighInPlayerId.value) ?? null
)

const togglePlayer = (player: Player) => {
    if (!isEligible(player)) return

    const index = form.registrations.findIndex(r => r.player_id === player.id)
    if (index !== -1) {
        form.registrations.splice(index, 1)
        if (activeWeighInPlayerId.value === player.id) closeWeighInModal()
        return
    }

    form.registrations.push({
        player_id: player.id,
        weigh_in_weight: null,
        weight_category_id: null
    })

    openWeighInModal(player.id)
}
const getRegistration = (playerId: number) => form.registrations.find(r => r.player_id === playerId)!

const openWeighInModal = (playerId: number) => {
    activeWeighInPlayerId.value = playerId
    draftWeight.value = getRegistration(playerId)?.weigh_in_weight ?? null
}

const closeWeighInModal = () => {
    activeWeighInPlayerId.value = null
    draftWeight.value = null
}

const saveWeighIn = () => {
    const player = activeWeighInPlayer.value
    if (!player) return

    const registration = getRegistration(player.id)
    registration.weigh_in_weight = draftWeight.value
    autoAssignWeight(player)
    closeWeighInModal()
}

const autoAssignWeight = (player: Player) => {
    const registration = getRegistration(player.id)
    const weight = registration.weigh_in_weight
    if (!weight || weight <= 0) {
        registration.weight_category_id = null
        return
    }
    const matched = props.weightCategories.find(w =>
        w.gender.toLowerCase() === player.gender.toLowerCase() &&
        w.age_category_id === player.age_category_id &&
        weight > w.min_weight &&
        weight <= w.max_weight
    )
    registration.weight_category_id = matched ? matched.id : null
}

const totalRegistered = computed(() => form.registrations.length)
const submit = () => form.put(route('admin.tournaments.update', props.tournament.id))
</script>

<template>
<Head :title="`Edit Tournament: ${props.tournament.name}`" />
<AppLayout :breadcrumbs="breadcrumbs">
<div class="p-6 space-y-8">

    <div class="space-y-4 max-w-xl">
        <h1 class="text-2xl font-bold">Edit Tournament</h1>
        <input v-model="form.name" placeholder="Tournament Name" class="w-full border bg-background rounded-lg p-2" />
        <input type="date" v-model="form.tournament_date" class="w-full border bg-background rounded-lg p-2" />
        <select v-model="form.status" class="w-full border bg-background rounded-lg p-2">
            <option value="draft">Draft</option>
            <option value="open">Open</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
        </select>
    </div>

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
                        <th class="p-3">Select</th>
                        <th class="p-3 text-left">Player</th>
                        <th class="p-3">Club</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Weigh-in (kg)</th>
                        <th class="p-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="player in filteredPlayers"
                        :key="player.id"
                        class="border-t"
                        :class="!isEligible(player) ? 'opacity-50 bg-muted/50' : 'hover:bg-muted/50'"
                    >
                        <td class="p-3 text-center">
                            <input
                                type="checkbox"
                                :checked="isSelected(player.id)"
                                :disabled="!isEligible(player)"
                                @change="togglePlayer(player)"
                            />
                        </td>
                        <td class="p-3 font-medium">
                            <div class="flex items-center gap-2">
                                <span>{{ player.full_name }}</span>
                                <span
                                    class="px-2 py-0.5 rounded text-xs font-medium"
                                    :class="String(player.gender).toLowerCase()==='male' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                                >
                                    {{ player.gender }}
                                </span>
                                <span
                                    v-if="!isEligible(player)"
                                    class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700"
                                >
                                    Not Eligible
                                </span>
                            </div>
                        </td>
                        <td class="p-3 text-center">{{ player.club }}</td>
                        <td class="p-3 text-center">{{ player.age_category }}</td>
                        <td class="p-3 text-center">
                            {{ isSelected(player.id) ? (getRegistration(player.id).weigh_in_weight ?? '-') : '-' }}
                        </td>
                        <td class="p-3 text-center">
                            <button
                                v-if="isSelected(player.id)"
                                type="button"
                                class="px-2 py-1 rounded border text-xs"
                                @click="openWeighInModal(player.id)"
                            >
                                {{ getRegistration(player.id).weigh_in_weight ? 'Edit Weigh-in' : 'Set Weigh-in' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <Button @click="submit" :disabled="form.processing">Update Tournament</Button>

    <div v-if="activeWeighInPlayer" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
        <div class="bg-background border rounded-xl w-full max-w-md p-5 space-y-4">
            <div>
                <h3 class="text-lg font-semibold">Weigh-in Entry</h3>
                <p class="text-sm text-muted-foreground">{{ activeWeighInPlayer.full_name }}</p>
            </div>

            <input
                type="number"
                step="0.01"
                v-model.number="draftWeight"
                class="w-full border bg-background rounded-lg p-2"
                placeholder="Enter weight in kg"
            />

            <div class="flex justify-end gap-2">
                <button type="button" class="px-3 py-2 rounded border hover:bg-muted" @click="closeWeighInModal">Cancel</button>
                <button type="button" class="px-3 py-2 rounded bg-primary text-primary-foreground hover:bg-primary/90" @click="saveWeighIn">Save</button>
            </div>
        </div>
    </div>

</div>
</AppLayout>
</template>
