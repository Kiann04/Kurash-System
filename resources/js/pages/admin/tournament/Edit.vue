<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { type BreadcrumbItem } from '@/types'
import { ref, computed, watch } from 'vue'

interface Player {
    id: number
    full_name: string
    gender: 'male' | 'female' | 'Male' | 'Female' | 'M' | 'F'
    club: string
}

interface TournamentWeightCategory {
    id: number
    gender: 'male' | 'female' | 'Male' | 'Female' | 'M' | 'F'
    age_category_id: number
    age_category_name: string
    name: string
}

interface TournamentRegistration {
    player_id: number
    weight_category_id: number | null
}

interface Registration {
    player_id: number
    tournament_weight_category_id: number
}

interface ImportRegistration {
    player_id: number
    tournament_weight_category_id: number
    player_name: string
    category_name: string
}

interface ImportRowResult {
    row: number
    status: 'matched' | 'unmatched_player' | 'unresolved_category' | 'duplicate'
    player: string
    category: string | null
    reason: string
}

interface ImportAnalysis {
    total_rows: number
    matched_count: number
    unmatched_player_count: number
    unresolved_category_count: number
    duplicate_count: number
    registrations: ImportRegistration[]
    rows: ImportRowResult[]
}

const props = defineProps<{
    tournament: {
        id: number
        name: string
        tournament_date: string
        status: string
        registrations: TournamentRegistration[]
    }
    players: Player[]
    tournamentWeightCategories: TournamentWeightCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: props.tournament.name, href: route('admin.tournaments.show', props.tournament.id) },
    { title: 'Edit', href: '' },
]

const form = useForm({
    name: props.tournament.name,
    tournament_date: props.tournament.tournament_date,
    status: props.tournament.status,
    registrations: props.tournament.registrations
        .filter((registration) => registration.weight_category_id !== null)
        .map((registration) => ({
            player_id: registration.player_id,
            tournament_weight_category_id: registration.weight_category_id as number,
        })) as Registration[],
})

const search = ref('')
const playerGenderFilter = ref<'all' | 'male' | 'female'>('all')
const openedSummaryCategoryId = ref<number | null>(null)
const importFile = ref<File | null>(null)
const importProcessing = ref(false)
const importError = ref('')
const importAnalysis = ref<ImportAnalysis | null>(null)

const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ?? ''
}

const onImportFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    importFile.value = target.files?.[0] ?? null
    importError.value = ''
}

const analyzeAndImportFile = async () => {
    if (!importFile.value) {
        importError.value = 'Please select a file first.'
        return
    }

    importError.value = ''
    importProcessing.value = true

    try {
        const payload = new FormData()
        payload.append('file', importFile.value)
        if (selectedCategoryId.value) {
            payload.append('fallback_category_id', String(selectedCategoryId.value))
        }

        const response = await fetch(route('admin.tournaments.import-registrations', undefined, false), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: payload,
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            const message = errorData?.message ?? 'Failed to analyze the file.'
            throw new Error(message)
        }

        const data = await response.json()
        const analysis = data.analysis as ImportAnalysis
        importAnalysis.value = analysis

        const existing = new Set(form.registrations.map((r) => `${r.player_id}-${r.tournament_weight_category_id}`))

        analysis.registrations.forEach((registration) => {
            const key = `${registration.player_id}-${registration.tournament_weight_category_id}`
            if (existing.has(key)) {
                return
            }

            form.registrations.push({
                player_id: registration.player_id,
                tournament_weight_category_id: registration.tournament_weight_category_id,
            })
            existing.add(key)
        })
    } catch (error) {
        importError.value = error instanceof Error ? error.message : 'Failed to analyze the file.'
    } finally {
        importProcessing.value = false
    }
}

const normalizeGender = (value: string | null | undefined): 'male' | 'female' | '' => {
    const normalized = String(value ?? '').trim().toLowerCase()

    if (normalized === 'male' || normalized === 'm') return 'male'
    if (normalized === 'female' || normalized === 'f') return 'female'
    return ''
}

const genderOptions = computed(() =>
    Array.from(
        new Set(
            props.tournamentWeightCategories
                .map((category) => normalizeGender(category.gender))
                .filter((gender): gender is 'male' | 'female' => gender === 'male' || gender === 'female'),
        ),
    ),
)

const selectedGender = ref<'male' | 'female'>(genderOptions.value[0] ?? 'male')

const ageCategoryOptions = computed(() => {
    const map = new Map<number, string>()

    props.tournamentWeightCategories
        .filter((category) => normalizeGender(category.gender) === selectedGender.value)
        .forEach((category) => {
            map.set(category.age_category_id, category.age_category_name || `Age Category #${category.age_category_id}`)
        })

    return Array.from(map.entries())
        .map(([id, name]) => ({ id, name }))
        .sort((a, b) => a.id - b.id)
})

const selectedAgeCategoryId = ref<number | null>(ageCategoryOptions.value[0]?.id ?? null)

const weightCategoryOptions = computed(() =>
    props.tournamentWeightCategories.filter((category) =>
        normalizeGender(category.gender) === selectedGender.value &&
        category.age_category_id === selectedAgeCategoryId.value,
    ),
)

const selectedCategoryId = ref<number | null>(weightCategoryOptions.value[0]?.id ?? null)

watch(selectedGender, () => {
    selectedAgeCategoryId.value = ageCategoryOptions.value[0]?.id ?? null
})

watch([selectedGender, selectedAgeCategoryId], () => {
    selectedCategoryId.value = weightCategoryOptions.value[0]?.id ?? null
}, { immediate: true })

const selectedCategory = computed(() =>
    props.tournamentWeightCategories.find((category) => category.id === selectedCategoryId.value) ?? null,
)

const filteredPlayers = computed(() => {
    return props.players.filter((player) => {
        const matchesGender =
            playerGenderFilter.value === 'all' || normalizeGender(player.gender) === playerGenderFilter.value

        const matchesSearch =
            !search.value ||
            player.full_name.toLowerCase().includes(search.value.toLowerCase()) ||
            (player.club ?? '').toLowerCase().includes(search.value.toLowerCase())

        return matchesGender && matchesSearch
    })
})

const getPlayerRegistrations = (playerId: number) =>
    form.registrations.filter((registration) => registration.player_id === playerId)

const getCategoryById = (categoryId: number) =>
    props.tournamentWeightCategories.find((category) => category.id === categoryId) ?? null

const getPlayerNameById = (playerId: number) =>
    props.players.find((player) => player.id === playerId)?.full_name ?? `#${playerId}`

const isSelectedInCurrentCategory = (playerId: number) =>
    form.registrations.some((registration) =>
        registration.player_id === playerId &&
        registration.tournament_weight_category_id === selectedCategoryId.value,
    )

const togglePlayerForSelectedCategory = (player: Player) => {
    if (!selectedCategoryId.value) {
        return
    }

    const existsInSelectedCategory = form.registrations.some((registration) =>
        registration.player_id === player.id &&
        registration.tournament_weight_category_id === selectedCategoryId.value,
    )

    if (existsInSelectedCategory) {
        form.registrations = form.registrations.filter((registration) =>
            !(registration.player_id === player.id && registration.tournament_weight_category_id === selectedCategoryId.value),
        )
        return
    }

    form.registrations.push({
        player_id: player.id,
        tournament_weight_category_id: selectedCategoryId.value,
    })
}

const getAssignedCategoryName = (playerId: number) => {
    const registrations = getPlayerRegistrations(playerId)
    if (registrations.length === 0) {
        return '-'
    }

    return registrations
        .map((registration) => getCategoryById(registration.tournament_weight_category_id)?.name ?? '-')
        .join(', ')
}

const getAssignedAgeCategoryName = (playerId: number) => {
    const registrations = getPlayerRegistrations(playerId)
    if (registrations.length === 0) {
        return '-'
    }

    return Array.from(
        new Set(
            registrations.map((registration) =>
                getCategoryById(registration.tournament_weight_category_id)?.age_category_name ?? '-',
            ),
        ),
    ).join(', ')
}

const registeredCategorySummary = computed(() => {
    const grouped = new Map<number, number[]>()

    form.registrations.forEach((registration) => {
        const playerIds = grouped.get(registration.tournament_weight_category_id) ?? []
        playerIds.push(registration.player_id)
        grouped.set(registration.tournament_weight_category_id, playerIds)
    })

    return Array.from(grouped.entries())
        .map(([categoryId, playerIds]) => {
            const category = getCategoryById(categoryId)

            return {
                category_id: categoryId,
                category_name: category?.name ?? '-',
                gender: category?.gender ?? '-',
                age_category: category?.age_category_name ?? '-',
                player_count: playerIds.length,
                players: playerIds.map((playerId) => ({
                    player_id: playerId,
                    full_name: getPlayerNameById(playerId),
                })),
            }
        })
        .sort((a, b) => b.player_count - a.player_count || a.category_name.localeCompare(b.category_name))
})

const openedSummary = computed(() =>
    registeredCategorySummary.value.find((item) => item.category_id === openedSummaryCategoryId.value) ?? null,
)

const openSummary = (categoryId: number) => {
    openedSummaryCategoryId.value = categoryId
}

const closeSummary = () => {
    openedSummaryCategoryId.value = null
}

const removeFromSummary = (categoryId: number, playerId: number) => {
    form.registrations = form.registrations.filter((registration) =>
        !(registration.tournament_weight_category_id === categoryId && registration.player_id === playerId),
    )
}

const totalRegistered = computed(() => form.registrations.length)
const uniqueRegisteredPlayers = computed(() => new Set(form.registrations.map((r) => r.player_id)).size)
const usedCategoryCount = computed(() => new Set(form.registrations.map((r) => r.tournament_weight_category_id)).size)
const selectedCategoryRegisteredCount = computed(() => {
    if (!selectedCategoryId.value) return 0

    return form.registrations.filter((registration) => registration.tournament_weight_category_id === selectedCategoryId.value).length
})
const isPlayerRegisteredAnywhere = (playerId: number) =>
    form.registrations.some((registration) => registration.player_id === playerId)

const submit = () => form.put(route('admin.tournaments.update', props.tournament.id))
</script>

<template>
<Head :title="`Edit Tournament: ${props.tournament.name}`" />
<AppLayout :breadcrumbs="breadcrumbs">
<div class="p-6 space-y-8">

    <div class="border rounded-xl bg-white p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Edit Tournament</h1>
            <Button @click="submit" :disabled="form.processing">Update Tournament</Button>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-1">
                <label class="text-sm font-medium">Tournament Name</label>
                <input v-model="form.name" placeholder="Tournament Name" class="w-full border rounded-lg p-2" />
                <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium">Tournament Date</label>
                <input type="date" v-model="form.tournament_date" class="w-full border rounded-lg p-2" />
                <p v-if="form.errors.tournament_date" class="text-xs text-red-600">{{ form.errors.tournament_date }}</p>
            </div>
            <div class="space-y-1">
                <label class="text-sm font-medium">Status</label>
                <select v-model="form.status" class="w-full border rounded-lg p-2">
                    <option value="draft">Draft</option>
                    <option value="open">Open</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>
                <p v-if="form.errors.status" class="text-xs text-red-600">{{ form.errors.status }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="border rounded-xl bg-white p-4 space-y-3 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Import Player List (Excel / Word)</h2>
                    <p class="text-xs text-muted-foreground">
                        Upload <code>.xlsx</code>, <code>.csv</code>, or <code>.docx</code>. System auto-maps by gender + age category + Uweight.
                    </p>
                </div>
                <Button size="sm" :disabled="importProcessing || !importFile" @click="analyzeAndImportFile">
                    {{ importProcessing ? 'Analyzing...' : 'Analyze & Add' }}
                </Button>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <input
                    type="file"
                    accept=".xlsx,.csv,.docx"
                    class="border rounded-md text-sm p-2"
                    @change="onImportFileChange"
                />
                <span v-if="selectedCategoryId" class="text-xs text-muted-foreground">
                    Fallback category: {{ getCategoryById(selectedCategoryId)?.name ?? '-' }}
                </span>
            </div>

            <p v-if="importError" class="text-xs text-red-600 font-medium">{{ importError }}</p>

            <div v-if="importAnalysis" class="grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
                <div class="border rounded-md p-2 bg-slate-50">
                    <p class="text-muted-foreground">Rows</p>
                    <p class="font-semibold">{{ importAnalysis.total_rows }}</p>
                </div>
                <div class="border rounded-md p-2 bg-emerald-50">
                    <p class="text-muted-foreground">Matched</p>
                    <p class="font-semibold">{{ importAnalysis.matched_count }}</p>
                </div>
                <div class="border rounded-md p-2 bg-amber-50">
                    <p class="text-muted-foreground">Unmatched Players</p>
                    <p class="font-semibold">{{ importAnalysis.unmatched_player_count }}</p>
                </div>
                <div class="border rounded-md p-2 bg-orange-50">
                    <p class="text-muted-foreground">Unresolved Categories</p>
                    <p class="font-semibold">{{ importAnalysis.unresolved_category_count }}</p>
                </div>
                <div class="border rounded-md p-2 bg-slate-100">
                    <p class="text-muted-foreground">Duplicates</p>
                    <p class="font-semibold">{{ importAnalysis.duplicate_count }}</p>
                </div>
            </div>

            <div v-if="importAnalysis && importAnalysis.rows.some((row) => row.status !== 'matched')" class="border rounded-lg overflow-hidden">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="p-2 text-left">Row</th>
                            <th class="p-2 text-left">Player</th>
                            <th class="p-2 text-left">Issue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in importAnalysis.rows.filter((item) => item.status !== 'matched').slice(0, 20)"
                            :key="`${row.row}-${row.status}-${row.player}`"
                            class="border-t"
                        >
                            <td class="p-2">{{ row.row }}</td>
                            <td class="p-2">{{ row.player }}</td>
                            <td class="p-2">{{ row.reason }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="text-xl font-semibold">Category First Registration</h2>
            <span class="text-sm text-muted-foreground">Total Registered: {{ totalRegistered }}</span>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <div class="border rounded-lg p-3 bg-white">
                <p class="text-xs text-muted-foreground">Total Entries</p>
                <p class="text-2xl font-semibold">{{ totalRegistered }}</p>
            </div>
            <div class="border rounded-lg p-3 bg-white">
                <p class="text-xs text-muted-foreground">Unique Players</p>
                <p class="text-2xl font-semibold">{{ uniqueRegisteredPlayers }}</p>
            </div>
            <div class="border rounded-lg p-3 bg-white">
                <p class="text-xs text-muted-foreground">Categories Used</p>
                <p class="text-2xl font-semibold">{{ usedCategoryCount }}</p>
            </div>
            <div class="border rounded-lg p-3 bg-white">
                <p class="text-xs text-muted-foreground">Selected Category Entries</p>
                <p class="text-2xl font-semibold">{{ selectedCategoryRegisteredCount }}</p>
            </div>
        </div>

        <div class="border rounded-lg overflow-hidden bg-white">
            <table class="w-full text-sm">
                <thead class="bg-muted/70">
                    <tr>
                        <th class="p-3 text-left">Gender</th>
                        <th class="p-3 text-left">Age Category</th>
                        <th class="p-3 text-left">Weight Category</th>
                        <th class="p-3 text-center">Players</th>
                        <th class="p-3 text-left">Who Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in registeredCategorySummary" :key="item.category_id" class="border-t">
                        <td class="p-3 capitalize">{{ item.gender }}</td>
                        <td class="p-3">{{ item.age_category }}</td>
                        <td class="p-3 font-medium">{{ item.category_name }}</td>
                        <td class="p-3 text-center">{{ item.player_count }}</td>
                        <td class="p-3">
                            <button type="button" class="text-blue-600 hover:underline" @click="openSummary(item.category_id)">
                                View {{ item.player_count }} player{{ item.player_count > 1 ? 's' : '' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="registeredCategorySummary.length === 0">
                        <td colspan="5" class="p-4 text-center text-muted-foreground">No category registrations yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border rounded-xl bg-white p-4 grid gap-4 md:grid-cols-3">
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Gender</label>
                <select v-model="selectedGender" class="border rounded-lg p-2 w-full">
                    <option v-for="gender in genderOptions" :key="gender" :value="gender">{{ gender }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Age Category</label>
                <select v-model="selectedAgeCategoryId" class="border rounded-lg p-2 w-full">
                    <option v-for="ageCategory in ageCategoryOptions" :key="ageCategory.id" :value="ageCategory.id">{{ ageCategory.name }}</option>
                </select>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Weight Category</label>
                <select v-model="selectedCategoryId" class="border rounded-lg p-2 w-full">
                    <option v-for="category in weightCategoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
            </div>
        </div>

        <div v-if="selectedCategory" class="text-sm border rounded-lg bg-blue-50 border-blue-200 px-3 py-2">
            Assigning players to
            <strong>{{ selectedCategory.name }}</strong>
            <span class="text-muted-foreground">({{ selectedCategory.age_category_name }} / {{ selectedCategory.gender }})</span>
        </div>

        <div class="flex gap-3 max-w-xl">
            <input v-model="search" placeholder="Search player by name or club..." class="border rounded-lg p-2 w-full" />
            <select v-model="playerGenderFilter" class="border rounded-lg p-2 w-36">
                <option value="all">All</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <div class="border rounded-lg overflow-hidden bg-white">
            <table class="w-full text-sm">
                <thead class="bg-muted/70">
                    <tr>
                        <th class="p-3">Select</th>
                        <th class="p-3 text-left">Player</th>
                        <th class="p-3">Club</th>
                        <th class="p-3">Age Category</th>
                        <th class="p-3">Assigned Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="player in filteredPlayers" :key="player.id" class="border-t" :class="isSelectedInCurrentCategory(player.id) ? 'bg-emerald-50' : ''">
                        <td class="p-3 text-center">
                            <input type="checkbox" :checked="isSelectedInCurrentCategory(player.id)" :disabled="!selectedCategoryId" @change="togglePlayerForSelectedCategory(player)" />
                        </td>
                        <td class="p-3 font-medium">
                            <div>{{ player.full_name }}</div>
                            <div v-if="isPlayerRegisteredAnywhere(player.id) && !isSelectedInCurrentCategory(player.id)" class="text-xs text-amber-700">
                                Already in another category
                            </div>
                        </td>
                        <td class="p-3 text-center">{{ player.club }}</td>
                        <td class="p-3 text-center">{{ getAssignedAgeCategoryName(player.id) }}</td>
                        <td class="p-3 text-center">{{ getAssignedCategoryName(player.id) }}</td>
                    </tr>
                    <tr v-if="filteredPlayers.length === 0">
                        <td colspan="5" class="p-4 text-center text-muted-foreground">No players found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div v-if="openedSummary" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">{{ openedSummary.category_name }} ({{ openedSummary.gender }} / {{ openedSummary.age_category }})</h3>
                    <p class="text-sm text-muted-foreground">{{ openedSummary.player_count }} registered</p>
                </div>
                <button type="button" class="px-3 py-1 rounded border" @click="closeSummary">Close</button>
            </div>

            <div class="border rounded-lg overflow-hidden max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted sticky top-0">
                        <tr>
                            <th class="p-3 text-left">Player</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="player in openedSummary.players" :key="`${openedSummary.category_id}-${player.player_id}`" class="border-t">
                            <td class="p-3">{{ player.full_name }}</td>
                            <td class="p-3 text-right">
                                <button type="button" class="px-2 py-1 rounded border text-red-600 border-red-200 hover:bg-red-50" @click="removeFromSummary(openedSummary.category_id, player.player_id)">
                                    Remove
                                </button>
                            </td>
                        </tr>
                        <tr v-if="openedSummary.players.length === 0">
                            <td colspan="2" class="p-4 text-center text-muted-foreground">No players in this category.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</AppLayout>
</template>
