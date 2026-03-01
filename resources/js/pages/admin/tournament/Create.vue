<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { ref, computed, watch, onMounted } from 'vue'
import { type BreadcrumbItem } from '@/types'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog'

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
    players: Player[]
    tournamentWeightCategories: TournamentWeightCategory[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tournaments', href: route('admin.tournaments.index') },
    { title: 'Create a Tournament', href: '' },
]

const form = useForm({
    name: '',
    tournament_date: '',
    status: 'draft',
    registrations: [] as Registration[],
})

const search = ref('')
const playerGenderFilter = ref<'all' | 'male' | 'female'>('all')
const openedSummaryCategoryId = ref<number | null>(null)
const importFile = ref<File | null>(null)
const importProcessing = ref(false)
const importError = ref('')
const importAnalysis = ref<ImportAnalysis | null>(null)

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

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('name')) form.name = params.get('name') || '';
    if (params.get('date')) form.tournament_date = params.get('date') || '';
    if (params.get('status')) form.status = params.get('status') || 'draft';
})

const isConfirmModalOpen = ref(false)

const submit = () => {
    isConfirmModalOpen.value = false
    form.post(route('admin.tournaments.store'))
}

const onImportFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    importFile.value = target.files?.[0] ?? null
    importError.value = ''
}

const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ?? ''
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
</script>

<template>
<Head title="Create Tournament" />
<AppLayout :breadcrumbs="breadcrumbs">
<div class="p-6 space-y-8">

    <div class="border rounded-xl bg-white p-4">
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <h1 class="text-xl font-bold">Create Tournament</h1>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-muted-foreground">
                    <span class="text-sm font-semibold text-slate-900">{{ form.name }}</span>
                    <span class="hidden sm:block h-3 w-px bg-slate-200"></span>
                    <span class="text-xs">Date: {{ form.tournament_date }}</span>
                    <span class="hidden sm:block h-3 w-px bg-slate-200"></span>
                    <span class="text-xs flex items-center gap-1.5">
                        Status: 
                        <span class="capitalize px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-medium border border-slate-200">{{ form.status }}</span>
                    </span>
                </div>
            </div>
            <Button @click="isConfirmModalOpen = true" :disabled="form.processing" size="sm" class="h-9">Save Tournament</Button>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <Dialog v-model:open="isConfirmModalOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>Confirm Tournament Details</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tournament Name</p>
                        <p class="font-medium text-slate-900">{{ form.name }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Date</p>
                        <p class="font-medium text-slate-900">{{ form.tournament_date }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</p>
                        <p class="font-medium capitalize text-slate-900">{{ form.status }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Registrations</p>
                        <p class="font-medium text-slate-900">{{ totalRegistered }}</p>
                    </div>
                </div>

                <div v-if="registeredCategorySummary.length > 0" class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Category Summary</p>
                    <div class="max-h-[200px] overflow-y-auto border rounded-lg">
                        <table class="w-full text-xs">
                            <thead class="bg-muted sticky top-0">
                                <tr>
                                    <th class="p-2 text-left">Category</th>
                                    <th class="p-2 text-center">Players</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in registeredCategorySummary" :key="item.category_id" class="border-t">
                                    <td class="p-2">{{ item.category_name }} ({{ item.age_category }} / {{ item.gender }})</td>
                                    <td class="p-2 text-center">{{ item.player_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isConfirmModalOpen = false">Cancel</Button>
                <Button @click="submit" :disabled="form.processing">Confirm & Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

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

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start">
            <!-- Left Side: Summary Stats -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Registration Stats</h2>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                    <div class="border rounded-lg p-3 bg-white shadow-sm">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Total Entries</p>
                        <p class="text-xl font-bold">{{ totalRegistered }}</p>
                    </div>
                    <div class="border rounded-lg p-3 bg-white shadow-sm">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Unique Players</p>
                        <p class="text-xl font-bold">{{ uniqueRegisteredPlayers }}</p>
                    </div>
                    <div class="border rounded-lg p-3 bg-white shadow-sm">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Categories Used</p>
                        <p class="text-xl font-bold">{{ usedCategoryCount }}</p>
                    </div>
                    <div class="border rounded-lg p-3 bg-white shadow-sm">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Selected Entries</p>
                        <p class="text-xl font-bold">{{ selectedCategoryRegisteredCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Right Side: Registered Categories Table -->
            <div class="lg:col-span-3 space-y-4">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <h2 class="text-lg font-semibold">Category First Registration</h2>
                    <span class="text-xs font-medium text-muted-foreground px-2 py-1 bg-slate-100 rounded-md">Total Registered: {{ totalRegistered }}</span>
                </div>

                <div class="border rounded-lg overflow-hidden bg-white shadow-sm">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="p-3 text-left font-semibold text-slate-700">Gender</th>
                                <th class="p-3 text-left font-semibold text-slate-700">Age Category</th>
                                <th class="p-3 text-left font-semibold text-slate-700">Weight Category</th>
                                <th class="p-3 text-center font-semibold text-slate-700">Players</th>
                                <th class="p-3 text-left font-semibold text-slate-700">Who Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in registeredCategorySummary" :key="item.category_id" class="hover:bg-slate-50 transition-colors">
                                <td class="p-3 capitalize">{{ item.gender }}</td>
                                <td class="p-3">{{ item.age_category }}</td>
                                <td class="p-3 font-medium">{{ item.category_name }}</td>
                                <td class="p-3 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-700 font-bold text-xs">
                                        {{ item.player_count }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 group" @click="openSummary(item.category_id)">
                                        <span>View Players</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="registeredCategorySummary.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted-foreground italic bg-slate-50/50">
                                    No category registrations yet. Select a category below to start adding players.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Category Selectors Moved Here -->
                <div class="border rounded-xl bg-white p-4 grid gap-4 md:grid-cols-3 shadow-sm">
                    <div class="space-y-1">
                        <label class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Gender</label>
                        <select v-model="selectedGender" class="border rounded-lg p-2 w-full text-sm">
                            <option v-for="gender in genderOptions" :key="gender" :value="gender">{{ gender }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Age Category</label>
                        <select v-model="selectedAgeCategoryId" class="border rounded-lg p-2 w-full text-sm">
                            <option v-for="ageCategory in ageCategoryOptions" :key="ageCategory.id" :value="ageCategory.id">{{ ageCategory.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">Weight Category</label>
                        <select v-model="selectedCategoryId" class="border rounded-lg p-2 w-full text-sm">
                            <option v-for="category in weightCategoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Assignment Banner, Search Bar, and Gender Filter Row -->
                <div class="flex flex-wrap items-center gap-3">
                    <div v-if="selectedCategory" class="flex-1 min-w-[200px] text-xs border rounded-lg bg-blue-50/50 border-blue-200 px-3 py-2 flex items-center gap-1.5 shadow-sm">
                        <span class="text-blue-700 font-medium">Assigning:</span>
                        <strong class="text-blue-900">{{ selectedCategory.name }}</strong>
                        <span class="text-blue-400">|</span>
                        <span class="text-blue-700 capitalize">{{ selectedCategory.gender }}</span>
                        <span class="text-blue-400">|</span>
                        <span class="text-blue-700">{{ selectedCategory.age_category_name }}</span>
                    </div>

                    <div class="flex items-center gap-2 bg-white border rounded-lg px-3 py-1 shadow-sm">
                        <label class="text-[10px] uppercase font-bold text-slate-400">Filter:</label>
                        <div class="flex items-center gap-1">
                            <button 
                                v-for="option in (['all', 'male', 'female'] as const)" 
                                :key="option"
                                @click="playerGenderFilter = option"
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase transition-colors"
                                :class="playerGenderFilter === option ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100'"
                            >
                                {{ option }}
                            </button>
                        </div>
                    </div>

                    <div class="relative flex-1 min-w-[200px]">
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Search players by name or club..." 
                            class="w-full border rounded-lg pl-8 pr-3 py-1.5 text-sm shadow-sm focus:ring-2 focus:ring-blue-500/20 transition-all"
                        />
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                </div>

                <!-- Players Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <div 
                        v-for="player in filteredPlayers" 
                        :key="player.id"
                        class="group border rounded-xl p-3 bg-white hover:border-blue-300 hover:shadow-md transition-all cursor-pointer relative overflow-hidden"
                        :class="[
                            isSelectedInCurrentCategory(player.id) ? 'border-blue-500 bg-blue-50/30' : 'border-slate-200'
                        ]"
                        @click="togglePlayerForSelectedCategory(player)"
                    >
                        <div v-if="isSelectedInCurrentCategory(player.id)" class="absolute top-0 right-0 p-1.5">
                            <div class="bg-blue-500 text-white rounded-full p-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm leading-tight group-hover:text-blue-700 transition-colors">
                                    {{ player.full_name }}
                                </h3>
                                <p class="text-[10px] text-muted-foreground font-medium uppercase tracking-wider mt-0.5">{{ player.club || 'No Club' }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <span 
                                    class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded border"
                                    :class="normalizeGender(player.gender) === 'male' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-pink-50 text-pink-700 border-pink-100'"
                                >
                                    {{ player.gender }}
                                </span>
                                
                                <div v-if="isPlayerRegisteredAnywhere(player.id)" class="flex flex-wrap gap-1">
                                    <span class="text-[9px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200">
                                        Registered
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredPlayers.length === 0" class="p-12 text-center border-2 border-dashed rounded-xl bg-slate-50/50">
                    <p class="text-slate-400 font-medium italic">No players found matching your search or filters.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Player Registration Detail Modal -->
<Dialog :open="!!openedSummary" @update:open="closeSummary">
    <DialogContent class="sm:max-w-[600px] max-h-[80vh] flex flex-col p-0">
        <DialogHeader class="p-6 pb-0">
            <DialogTitle class="flex items-center gap-3">
                <span class="text-2xl">📋</span>
                <div>
                    <span class="block text-xl font-bold">{{ openedSummary?.category_name }}</span>
                    <span class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                        {{ openedSummary?.age_category }} / {{ openedSummary?.gender }}
                    </span>
                </div>
            </DialogTitle>
        </DialogHeader>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">Registered Players ({{ openedSummary?.player_count }})</h3>
                </div>

                <div class="grid gap-2">
                    <div 
                        v-for="player in openedSummary?.players" 
                        :key="player.player_id"
                        class="flex items-center justify-between p-3 rounded-lg border border-slate-100 bg-slate-50/50 hover:bg-slate-50 transition-colors group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-400">
                                {{ player.full_name.charAt(0) }}
                            </div>
                            <span class="font-semibold text-slate-900">{{ player.full_name }}</span>
                        </div>
                        <button 
                            @click="removeFromSummary(openedSummary!.category_id, player.player_id)"
                            class="text-xs font-bold text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <DialogFooter class="p-6 bg-slate-50 border-t">
            <Button variant="outline" @click="closeSummary">Close</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
</AppLayout>
</template>
