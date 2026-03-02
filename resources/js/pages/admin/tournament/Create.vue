<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { route } from 'ziggy-js'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { 
    Trophy, 
    Upload, 
    Users, 
    Search, 
    Check, 
    X,
    UserPlus,
    FileSpreadsheet,
    AlertCircle
} from 'lucide-vue-next'
import { ref, computed, watch, onMounted } from 'vue'
import { type BreadcrumbItem } from '@/types'

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
    { title: 'Create Tournament', href: '' },
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

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
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
        <div class="flex-1 space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight dark:text-slate-100">Create Tournament</h1>
                    <p class="text-muted-foreground">Fill in the details to create a new tournament.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="$inertia.visit(route('admin.tournaments.index'))">Cancel</Button>
                    <Button @click="isConfirmModalOpen = true" :disabled="form.processing">
                        <Trophy class="mr-2 h-4 w-4" />
                        Create Tournament
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Tournament Details Card -->
                <Card class="dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader>
                        <CardTitle class="dark:text-slate-100">Tournament Details</CardTitle>
                        <CardDescription>Basic information about the tournament.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name" class="dark:text-slate-300">Tournament Name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. National Championship 2024" class="dark:bg-slate-950 dark:border-slate-800" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="date" class="dark:text-slate-300">Date</Label>
                                <Input id="date" type="date" v-model="form.tournament_date" class="dark:bg-slate-950 dark:border-slate-800" />
                                <p v-if="form.errors.tournament_date" class="text-sm text-destructive">{{ form.errors.tournament_date }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="status" class="dark:text-slate-300">Status</Label>
                                <div class="relative">
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                    >
                                        <option value="draft">Draft</option>
                                        <option value="open">Open</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-400">
                                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                                    </div>
                                </div>
                                <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Import Section Card -->
                <Card class="dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader>
                        <CardTitle class="dark:text-slate-100">Import Registrations</CardTitle>
                        <CardDescription>Upload a player list (.xlsx, .csv, .docx) to auto-map entries.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2">
                            <Input
                                type="file"
                                accept=".xlsx,.csv,.docx"
                                class="cursor-pointer dark:bg-slate-950 dark:border-slate-800 dark:file:text-slate-100"
                                @change="onImportFileChange"
                            />
                            <Button 
                                variant="secondary" 
                                :disabled="importProcessing || !importFile" 
                                @click="analyzeAndImportFile"
                            >
                                <Upload class="mr-2 h-4 w-4" />
                                {{ importProcessing ? 'Analyzing...' : 'Import' }}
                            </Button>
                        </div>
                        
                        <div v-if="selectedCategoryId" class="text-xs text-muted-foreground flex items-center gap-1">
                            <AlertCircle class="h-3 w-3" />
                            Fallback category: <span class="font-medium">{{ getCategoryById(selectedCategoryId)?.name ?? '-' }}</span>
                        </div>

                        <p v-if="importError" class="text-sm font-medium text-destructive">{{ importError }}</p>

                        <!-- Import Analysis Stats -->
                        <div v-if="importAnalysis" class="grid grid-cols-2 gap-2 text-xs sm:grid-cols-3">
                            <div class="rounded-md border bg-muted/50 p-2">
                                <p class="text-muted-foreground">Total Rows</p>
                                <p class="text-lg font-bold">{{ importAnalysis.total_rows }}</p>
                            </div>
                            <div class="rounded-md border bg-green-50 p-2 text-green-700 border-green-200">
                                <p class="text-xs font-medium opacity-80">Matched</p>
                                <p class="text-lg font-bold">{{ importAnalysis.matched_count }}</p>
                            </div>
                            <div class="rounded-md border bg-amber-50 p-2 text-amber-700 border-amber-200">
                                <p class="text-xs font-medium opacity-80">Unmatched</p>
                                <p class="text-lg font-bold">{{ importAnalysis.unmatched_player_count }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Import Issues Table (Conditional) -->
            <Card v-if="importAnalysis && importAnalysis.rows.some((row) => row.status !== 'matched')" class="border-amber-200 bg-amber-50/30 dark:bg-amber-900/10 dark:border-amber-800">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base text-amber-800 dark:text-amber-200">Import Issues</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border bg-white dark:bg-slate-950 dark:border-slate-800">
                        <Table class="text-xs">
                            <TableHeader class="bg-muted/50 dark:bg-slate-900/50">
                                <TableRow>
                                    <TableHead class="p-2 text-left font-medium">Row</TableHead>
                                    <TableHead class="p-2 text-left font-medium">Player</TableHead>
                                    <TableHead class="p-2 text-left font-medium">Issue</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="row in importAnalysis.rows.filter((item) => item.status !== 'matched').slice(0, 20)"
                                    :key="`${row.row}-${row.status}-${row.player}`"
                                    class="border-t last:border-0 dark:border-slate-800"
                                >
                                    <TableCell class="p-2">{{ row.row }}</TableCell>
                                    <TableCell class="p-2 font-medium dark:text-slate-200">{{ row.player }}</TableCell>
                                    <TableCell class="p-2 text-muted-foreground">{{ row.reason }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <Separator />

            <!-- Registration Manager Section -->
            <div class="space-y-6">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl font-bold tracking-tight">Registration Manager</h2>
                    <p class="text-muted-foreground">Manually assign players to categories.</p>
                </div>

                <!-- Registration Stats Cards -->
                <div class="grid gap-4 md:grid-cols-4">
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-100">Total Entries</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ totalRegistered }}</div>
                        </CardContent>
                    </Card>
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-100">Unique Players</CardTitle>
                            <UserPlus class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ uniqueRegisteredPlayers }}</div>
                        </CardContent>
                    </Card>
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-100">Categories Used</CardTitle>
                            <FileSpreadsheet class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ usedCategoryCount }}</div>
                        </CardContent>
                    </Card>
                    <Card :class="selectedCategoryId ? 'bg-primary/5 border-primary/20 dark:bg-primary/10 dark:border-primary/30' : 'dark:bg-slate-950 dark:border-slate-800'">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-100">Selected Category</CardTitle>
                            <Check class="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold text-primary">{{ selectedCategoryRegisteredCount }}</div>
                            <p class="text-xs text-muted-foreground mt-1 truncate" v-if="selectedCategory">
                                {{ selectedCategory.name }}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Left Column: Category Selection & Player List -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Filters -->
                        <Card class="dark:bg-slate-950 dark:border-slate-800">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base dark:text-slate-100">1. Select Category</CardTitle>
                            </CardHeader>
                            <CardContent class="grid gap-4 sm:grid-cols-3">
                                <div class="space-y-2">
                                    <Label class="dark:text-slate-300">Gender</Label>
                                    <div class="relative">
                                        <select
                                            v-model="selectedGender"
                                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 appearance-none capitalize dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                        >
                                            <option v-for="gender in genderOptions" :key="gender" :value="gender">
                                                {{ gender }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label>Age Category</Label>
                                    <div class="relative">
                                        <select
                                            v-model="selectedAgeCategoryId"
                                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 appearance-none dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                        >
                                            <option v-for="age in ageCategoryOptions" :key="age.id" :value="age.id">
                                                {{ age.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label>Weight Category</Label>
                                    <div class="relative">
                                        <select
                                            v-model="selectedCategoryId"
                                            class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 appearance-none dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                        >
                                            <option v-for="cat in weightCategoryOptions" :key="cat.id" :value="cat.id">
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Player Selection -->
                        <Card class="dark:bg-slate-950 dark:border-slate-800">
                            <CardHeader class="pb-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <CardTitle class="text-base dark:text-slate-100">2. Assign Players</CardTitle>
                                    <div class="relative w-full sm:w-64">
                                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            v-model="search"
                                            placeholder="Search players..."
                                            class="pl-8 dark:bg-slate-950 dark:border-slate-800"
                                        />
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="rounded-md border dark:border-slate-800">
                                    <div class="max-h-125 overflow-y-auto">
                                        <Table class="text-sm">
                                            <TableHeader class="bg-muted/50 sticky top-0 z-10 dark:bg-slate-900/50">
                                                <TableRow>
                                                    <TableHead class="h-10 px-4 text-left font-medium">Player</TableHead>
                                                    <TableHead class="h-10 px-4 text-left font-medium">Current Assignments</TableHead>
                                                    <TableHead class="h-10 px-4 text-right font-medium">Action</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow
                                                    v-for="player in filteredPlayers"
                                                    :key="player.id"
                                                    class="border-b last:border-0 hover:bg-muted/50 transition-colors dark:border-slate-800"
                                                    :class="{'bg-primary/5': isSelectedInCurrentCategory(player.id)}"
                                                >
                                                    <TableCell class="p-3">
                                                        <div class="flex items-center gap-3">
                                                            <Avatar class="h-8 w-8">
                                                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.full_name}&background=random`" />
                                                                <AvatarFallback class="dark:bg-slate-800 dark:text-slate-300">{{ getInitials(player.full_name) }}</AvatarFallback>
                                                            </Avatar>
                                                            <div>
                                                                <div class="font-medium dark:text-slate-100">{{ player.full_name }}</div>
                                                                <div class="text-xs text-muted-foreground">{{ player.club }}</div>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell class="p-3">
                                                        <div class="text-xs space-y-1">
                                                            <div v-if="getAssignedCategoryName(player.id) !== '-'" class="flex flex-wrap gap-1">
                                                                <Badge variant="outline" class="text-[10px] font-normal dark:border-slate-700 dark:text-slate-300">
                                                                    {{ getAssignedCategoryName(player.id) }}
                                                                </Badge>
                                                            </div>
                                                            <span v-else class="text-muted-foreground">-</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell class="p-3 text-right">
                                                        <Button
                                                            size="sm"
                                                            :variant="isSelectedInCurrentCategory(player.id) ? 'default' : 'outline'"
                                                            class="h-8"
                                                            @click="togglePlayerForSelectedCategory(player)"
                                                            :disabled="!selectedCategoryId"
                                                        >
                                                            <span v-if="isSelectedInCurrentCategory(player.id)" class="flex items-center">
                                                                <Check class="mr-1 h-3 w-3" /> Added
                                                            </span>
                                                            <span v-else>Add</span>
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                                <TableRow v-if="filteredPlayers.length === 0">
                                                    <TableCell colspan="3" class="p-8 text-center text-muted-foreground">
                                                        No players found matching your criteria.
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column: Summary -->
                    <div class="space-y-6">
                        <Card class="h-full flex flex-col dark:bg-slate-950 dark:border-slate-800">
                            <CardHeader>
                                <CardTitle class="text-base dark:text-slate-100">Registration Summary</CardTitle>
                                <CardDescription>Overview of all assignments.</CardDescription>
                            </CardHeader>
                            <CardContent class="flex-1 overflow-hidden">
                                <div class="rounded-md border h-full max-h-150 overflow-y-auto dark:border-slate-800">
                                    <Table class="text-xs">
                                        <TableHeader class="bg-muted/50 sticky top-0 dark:bg-slate-900/50">
                                            <TableRow>
                                                <TableHead class="p-2 text-left font-medium">Category</TableHead>
                                                <TableHead class="p-2 text-center font-medium">Count</TableHead>
                                                <TableHead class="p-2 text-right font-medium"></TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <template v-for="item in registeredCategorySummary" :key="item.category_id">
                                                <TableRow class="border-b last:border-0 hover:bg-muted/50 dark:border-slate-800">
                                                    <TableCell class="p-2">
                                                        <div class="font-medium dark:text-slate-100">{{ item.category_name }}</div>
                                                        <div class="text-[10px] text-muted-foreground capitalize">
                                                            {{ item.gender }} • {{ item.age_category }}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell class="p-2 text-center font-bold dark:text-slate-100">{{ item.player_count }}</TableCell>
                                                    <TableCell class="p-2 text-right">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-6 w-6"
                                                            @click="openedSummaryCategoryId === item.category_id ? closeSummary() : openSummary(item.category_id)"
                                                        >
                                                            <Users class="h-3 w-3" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                                <!-- Expanded Details -->
                                                <TableRow v-if="openedSummaryCategoryId === item.category_id" class="bg-muted/30 dark:bg-slate-900/30">
                                                    <TableCell colspan="3" class="p-2">
                                                        <div class="space-y-1">
                                                            <div v-for="p in item.players" :key="p.player_id" class="flex items-center justify-between rounded bg-white p-1.5 border shadow-sm dark:bg-slate-950 dark:border-slate-800">
                                                                <span class="truncate pr-2 dark:text-slate-300">{{ p.full_name }}</span>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    class="h-5 w-5 text-destructive hover:text-destructive/90"
                                                                    @click="removeFromSummary(item.category_id, p.player_id)"
                                                                >
                                                                    <X class="h-3 w-3" />
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            </template>
                                            <TableRow v-if="registeredCategorySummary.length === 0">
                                                <TableCell colspan="3" class="p-6 text-center text-muted-foreground">
                                                    No registrations yet.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Confirmation Modal -->
            <Dialog v-model:open="isConfirmModalOpen">
                <DialogContent class="sm:max-w-xl dark:bg-slate-950 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-slate-100">Confirm Tournament Creation</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4 text-sm rounded-lg border p-4 bg-muted/20 dark:bg-slate-900/50 dark:border-slate-800">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-slate-400">Tournament Name</p>
                                <p class="font-medium dark:text-slate-200">{{ form.name || 'Untitled Tournament' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-slate-400">Date</p>
                                <p class="font-medium dark:text-slate-200">{{ form.tournament_date || 'Not set' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-slate-400">Status</p>
                                <Badge variant="outline" class="capitalize dark:border-slate-700 dark:text-slate-300">{{ form.status }}</Badge>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-slate-400">Total Registrations</p>
                                <p class="font-medium dark:text-slate-200">{{ totalRegistered }}</p>
                            </div>
                        </div>

                        <div v-if="registeredCategorySummary.length > 0" class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground dark:text-slate-400">Category Breakdown</p>
                            <div class="max-h-50 overflow-y-auto border rounded-lg dark:border-slate-800">
                                <Table class="text-xs">
                                    <TableHeader class="bg-muted/50 sticky top-0 dark:bg-slate-900/50">
                                        <TableRow>
                                            <TableHead class="p-2 text-left font-medium">Category</TableHead>
                                            <TableHead class="p-2 text-center font-medium">Players</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="item in registeredCategorySummary" :key="item.category_id" class="border-t dark:border-slate-800">
                                            <TableCell class="p-2">
                                                <span class="font-medium dark:text-slate-200">{{ item.category_name }}</span>
                                                <span class="text-muted-foreground ml-1 dark:text-slate-400">({{ item.age_category }} / {{ item.gender }})</span>
                                            </TableCell>
                                            <TableCell class="p-2 text-center dark:text-slate-200">{{ item.player_count }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isConfirmModalOpen = false">Cancel</Button>
                        <Button @click="submit" :disabled="form.processing">Confirm & Create</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
