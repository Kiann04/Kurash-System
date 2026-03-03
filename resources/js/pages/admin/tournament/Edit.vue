<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { 
    Upload, 
    Users, 
    Search, 
    Check, 
    X,
    UserPlus,
    FileSpreadsheet,
    AlertCircle,
    Save,
    Plus,
    Trash2,
    ChevronDown,
    Loader2
} from 'lucide-vue-next'
import { ref, computed, watch } from 'vue'
import { route } from 'ziggy-js'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AppLayout.vue'
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

const localWeightCategories = ref<TournamentWeightCategory[]>([...props.tournamentWeightCategories])

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

const removeFromSummary = (categoryId: number, playerId: number) => {
    form.registrations = form.registrations.filter((registration) =>
        !(registration.player_id === playerId && registration.tournament_weight_category_id === categoryId),
    )
}

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
            localWeightCategories.value
                .map((category) => normalizeGender(category.gender))
                .filter((gender): gender is 'male' | 'female' => gender === 'male' || gender === 'female'),
        ),
    ),
)

const selectedGender = ref<'male' | 'female'>(genderOptions.value[0] ?? 'male')

const ageCategoryOptions = computed(() => {
    const map = new Map<number, string>()

    localWeightCategories.value
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
    localWeightCategories.value
        .filter((category) =>
            normalizeGender(category.gender) === selectedGender.value &&
            category.age_category_id === selectedAgeCategoryId.value,
        )
        .slice()
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true })),
)

const selectedCategoryId = ref<number | null>(weightCategoryOptions.value[0]?.id ?? null)

watch(selectedGender, () => {
    selectedAgeCategoryId.value = ageCategoryOptions.value[0]?.id ?? null
})

watch([selectedGender, selectedAgeCategoryId], () => {
    selectedCategoryId.value = weightCategoryOptions.value[0]?.id ?? null
}, { immediate: true })

const selectedCategory = computed(() =>
    localWeightCategories.value.find((category) => category.id === selectedCategoryId.value) ?? null,
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
    localWeightCategories.value.find((category) => category.id === categoryId) ?? null

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

const totalRegistered = computed(() => form.registrations.length)
const uniqueRegisteredPlayers = computed(() => new Set(form.registrations.map((r) => r.player_id)).size)
const usedCategoryCount = computed(() => new Set(form.registrations.map((r) => r.tournament_weight_category_id)).size)
const selectedCategoryRegisteredCount = computed(() => {
    if (!selectedCategoryId.value) return 0

    return form.registrations.filter((registration) => registration.tournament_weight_category_id === selectedCategoryId.value).length
})

const isConfirmModalOpen = ref(false)

const submit = () => {
    isConfirmModalOpen.value = false
    form.put(route('admin.tournaments.update', props.tournament.id))
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

// Create Weight Category State
const isCreateCategoryOpen = ref(false)
const isCreateCategoryLoading = ref(false)
const createCategoryForm = ref({
    name: '',
    error: ''
})

const openCreateCategoryModal = () => {
    if (!selectedGender.value || !selectedAgeCategoryId.value) {
        // You could use a toast here if available, or just ignore since the button is likely context-aware
        // For now, setting a temporary error on the form or just showing the modal with a warning if needed
        // But the user flow suggests they should select these first.
        // Let's assume the button is enabled only when context is valid or we show an error in the modal.
        createCategoryForm.value.error = 'Please select Gender and Age Category first.'
    } else {
        createCategoryForm.value.error = ''
    }
    createCategoryForm.value.name = ''
    isCreateCategoryOpen.value = true
}

const submitCreateWeightCategory = async () => {
    if (!createCategoryForm.value.name.trim()) {
        createCategoryForm.value.error = 'Weight category name is required.'
        return
    }

    if (!selectedGender.value || !selectedAgeCategoryId.value) {
        createCategoryForm.value.error = 'Gender and Age Category must be selected.'
        return
    }

    isCreateCategoryLoading.value = true
    createCategoryForm.value.error = ''

    try {
        const response = await fetch(route('admin.weight-categories.store', undefined, false), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: createCategoryForm.value.name,
                gender: selectedGender.value,
                age_category_id: selectedAgeCategoryId.value,
            }),
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            const message = errorData?.message ?? 'Failed to create weight category.'
            throw new Error(message)
        }

        const data = await response.json()
        const newCategory = data.category as TournamentWeightCategory
        const exists = localWeightCategories.value.some((category) => category.id === newCategory.id)

        if (!exists) {
            localWeightCategories.value.push(newCategory)
        }

        selectedCategoryId.value = newCategory.id
        isCreateCategoryOpen.value = false
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Failed to create weight category.'
        createCategoryForm.value.error = message
    } finally {
        isCreateCategoryLoading.value = false
    }
}

// Delete Weight Category State
const isDeleteCategoryOpen = ref(false)
const isDeleteCategoryLoading = ref(false)
const deleteCategoryError = ref('')

const openDeleteCategoryModal = () => {
    if (!selectedCategoryId.value) return
    deleteCategoryError.value = ''
    isDeleteCategoryOpen.value = true
}

const submitDeleteWeightCategory = async () => {
    if (!selectedCategoryId.value) return

    isDeleteCategoryLoading.value = true
    deleteCategoryError.value = ''

    try {
        const response = await fetch(route('admin.weight-categories.destroy', selectedCategoryId.value, false), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            const message = errorData?.message ?? errorData?.errors?.weight_category?.[0] ?? 'Failed to delete weight category.'
            throw new Error(message)
        }

        localWeightCategories.value = localWeightCategories.value.filter((category) => category.id !== selectedCategoryId.value)
        selectedCategoryId.value = weightCategoryOptions.value[0]?.id ?? null
        isDeleteCategoryOpen.value = false
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Failed to delete weight category.'
        deleteCategoryError.value = message
    } finally {
        isDeleteCategoryLoading.value = false
    }
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
    <Head :title="`Edit Tournament: ${props.tournament.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Edit Tournament</h1>
                    <p class="text-sm text-muted-foreground">Modify tournament details and registrations.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Button variant="outline" @click="$inertia.visit(route('admin.tournaments.index'))" class="dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                        Cancel
                    </Button>
                    <Button @click="isConfirmModalOpen = true" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white dark:bg-indigo-600 dark:hover:bg-indigo-700 shadow-sm">
                        <Save class="mr-2 h-4 w-4" />
                        Update Tournament
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Tournament Details Card -->
                <Card class="shadow-sm border-slate-200 dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 pb-4">
                        <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">Tournament Details</CardTitle>
                        <CardDescription>Basic information about the tournament.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-6">
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
                                <Label class="dark:text-slate-300">Status</Label>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button 
                                            variant="outline" 
                                            :disabled="props.tournament.status === 'completed'"
                                            class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 capitalize font-normal"
                                        >
                                            {{ form.status }}
                                            <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800">
                                        <DropdownMenuItem @click="form.status = 'draft'" class="dark:text-slate-100 cursor-pointer">
                                            Draft
                                            <Check v-if="form.status === 'draft'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="form.status = 'open'" class="dark:text-slate-100 cursor-pointer">
                                            Open
                                            <Check v-if="form.status === 'open'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="form.status = 'ongoing'" class="dark:text-slate-100 cursor-pointer">
                                            Ongoing
                                            <Check v-if="form.status === 'ongoing'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                        <DropdownMenuItem v-if="props.tournament.status === 'completed'" @click="form.status = 'completed'" class="dark:text-slate-100 cursor-pointer">
                                            Completed
                                            <Check v-if="form.status === 'completed'" class="ml-auto h-4 w-4" />
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Import Section Card -->
                <Card class="shadow-sm border-slate-200 dark:bg-slate-950 dark:border-slate-800">
                    <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 pb-4">
                        <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">Import Registrations</CardTitle>
                        <CardDescription>Upload a player list (.xlsx, .csv, .docx) to auto-map entries.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-6">
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
                                class="whitespace-nowrap shadow-sm hover:bg-slate-200 dark:hover:bg-slate-800"
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
                    <h2 class="text-xl font-bold tracking-tight dark:text-slate-100">Registration Manager</h2>
                    <p class="text-muted-foreground">Manually assign players to categories.</p>
                </div>

                <!-- Registration Stats Cards -->
                <div class="grid gap-4 md:grid-cols-4">
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-200">Total Entries</CardTitle>
                            <Users class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ totalRegistered }}</div>
                        </CardContent>
                    </Card>
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-200">Unique Players</CardTitle>
                            <UserPlus class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ uniqueRegisteredPlayers }}</div>
                        </CardContent>
                    </Card>
                    <Card class="dark:bg-slate-950 dark:border-slate-800">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-200">Categories Used</CardTitle>
                            <FileSpreadsheet class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold dark:text-slate-100">{{ usedCategoryCount }}</div>
                        </CardContent>
                    </Card>
                    <Card :class="selectedCategoryId ? 'bg-primary/5 border-primary/20 dark:bg-primary/10 dark:border-primary/30' : 'dark:bg-slate-950 dark:border-slate-800'">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium dark:text-slate-200">Selected Category</CardTitle>
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
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 capitalize">
                                                {{ selectedGender }}
                                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800">
                                            <DropdownMenuItem 
                                                v-for="gender in genderOptions" 
                                                :key="gender" 
                                                @click="selectedGender = gender"
                                                class="capitalize dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer"
                                            >
                                                {{ gender }}
                                                <Check v-if="selectedGender === gender" class="ml-auto h-4 w-4" />
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                                <div class="space-y-2">
                                    <Label class="dark:text-slate-300">Age Category</Label>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100">
                                                <span class="truncate">{{ ageCategoryOptions.find(opt => opt.id === selectedAgeCategoryId)?.name ?? 'Select Age Category' }}</span>
                                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800 max-h-75 overflow-y-auto">
                                            <DropdownMenuItem 
                                                v-for="age in ageCategoryOptions" 
                                                :key="age.id" 
                                                @click="selectedAgeCategoryId = age.id"
                                                class="dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer"
                                            >
                                                {{ age.name }}
                                                <Check v-if="selectedAgeCategoryId === age.id" class="ml-auto h-4 w-4" />
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                                <div class="space-y-2">
                                    <Label class="dark:text-slate-300">Weight Category</Label>
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <DropdownMenu v-if="weightCategoryOptions.length > 0">
                                                <DropdownMenuTrigger as-child>
                                                    <Button variant="outline" class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100">
                                                        <span class="truncate">{{ weightCategoryOptions.find(opt => opt.id === selectedCategoryId)?.name ?? 'Select Weight Category' }}</span>
                                                        <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800 max-h-60 overflow-y-auto">
                                                    <DropdownMenuItem 
                                                        v-for="cat in weightCategoryOptions" 
                                                        :key="cat.id" 
                                                        @click="selectedCategoryId = cat.id"
                                                        class="dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer"
                                                    >
                                                        {{ cat.name }}
                                                        <Check v-if="selectedCategoryId === cat.id" class="ml-auto h-4 w-4" />
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                            <Button v-else variant="outline" disabled class="w-full justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 opacity-50 cursor-not-allowed">
                                                No categories available
                                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                            </Button>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="h-10 w-10 shrink-0"
                                                type="button"
                                                @click="openCreateCategoryModal"
                                                aria-label="Add weight category"
                                            >
                                                <Plus class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                class="h-10 w-10 shrink-0 text-destructive hover:text-destructive/90"
                                                type="button"
                                                :disabled="!selectedCategoryId"
                                                @click="openDeleteCategoryModal"
                                                aria-label="Remove weight category"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        Add or remove categories for the selected gender and age group.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Player Selection -->
                        <Card class="dark:bg-slate-950 dark:border-slate-800">
                            <CardHeader class="pb-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <CardTitle class="text-base dark:text-slate-100">2. Assign Players</CardTitle>
                                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="outline" class="w-full sm:w-32 justify-between dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100 h-9 px-3">
                                                     <span class="capitalize">{{ playerGenderFilter === 'all' ? 'All Genders' : playerGenderFilter }}</span>
                                                    <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) dark:bg-slate-950 dark:border-slate-800">
                                                <DropdownMenuItem @click="playerGenderFilter = 'all'" class="dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer">
                                                    All Genders
                                                    <Check v-if="playerGenderFilter === 'all'" class="ml-auto h-4 w-4" />
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="playerGenderFilter = 'male'" class="dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer">
                                                    Male
                                                    <Check v-if="playerGenderFilter === 'male'" class="ml-auto h-4 w-4" />
                                                </DropdownMenuItem>
                                                <DropdownMenuItem @click="playerGenderFilter = 'female'" class="dark:text-slate-100 dark:focus:bg-slate-900 cursor-pointer">
                                                    Female
                                                    <Check v-if="playerGenderFilter === 'female'" class="ml-auto h-4 w-4" />
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                        <div class="relative w-full sm:w-64">
                                            <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                v-model="search"
                                                placeholder="Search players..."
                                                class="pl-8 dark:bg-slate-950 dark:border-slate-800"
                                            />
                                        </div>
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
                                                    class="border-b last:border-0 hover:bg-muted/50 transition-colors dark:border-slate-800 dark:hover:bg-slate-900/50"
                                                    :class="{'bg-primary/5 dark:bg-primary/10': isSelectedInCurrentCategory(player.id)}"
                                                >
                                                    <TableCell class="p-3">
                                                        <div class="flex items-center gap-3">
                                                            <Avatar class="h-8 w-8">
                                                                <AvatarImage :src="`https://ui-avatars.com/api/?name=${player.full_name}&background=random`" />
                                                                <AvatarFallback>{{ getInitials(player.full_name) }}</AvatarFallback>
                                                            </Avatar>
                                                            <div>
                                                                <div class="font-medium dark:text-slate-200">{{ player.full_name }}</div>
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
                                                <TableHead class="p-2 text-center font-medium w-16">Count</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <template v-for="item in registeredCategorySummary" :key="item.category_id">
                                                <TableRow class="border-b last:border-0 hover:bg-muted/50 dark:border-slate-800 dark:hover:bg-slate-900/50">
                                                    <TableCell class="p-2">
                                                        <div class="font-medium dark:text-slate-200">{{ item.category_name }}</div>
                                                        <div class="text-xs text-muted-foreground capitalize">
                                                            {{ item.gender }} • {{ item.age_category }}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell class="p-2 text-center font-bold dark:text-slate-200">{{ item.player_count }}</TableCell>
                                                </TableRow>
                                                <!-- Expanded Details -->
                                                <TableRow class="bg-muted/30 dark:bg-slate-900/30 border-b dark:border-slate-800">
                                                    <TableCell colspan="2" class="p-2">
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
                                                <TableCell colspan="2" class="p-6 text-center text-muted-foreground">
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
                <DialogContent class="sm:max-w-150 dark:bg-slate-950 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-slate-100">Confirm Updates</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-4">
                        <div class="grid grid-cols-2 gap-4 text-sm rounded-lg border p-4 bg-muted/20 dark:bg-slate-900/50 dark:border-slate-800">
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Tournament Name</p>
                                <p class="font-medium dark:text-slate-200">{{ form.name || 'Untitled Tournament' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Date</p>
                                <p class="font-medium dark:text-slate-200">{{ form.tournament_date || 'Not set' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Status</p>
                                <Badge variant="outline" class="capitalize dark:border-slate-700 dark:text-slate-300">{{ form.status }}</Badge>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Players</p>
                                <p class="font-medium dark:text-slate-200">{{ uniqueRegisteredPlayers }}</p>
                            </div>
                        </div>

                        <div class="rounded-lg border p-4 dark:border-slate-800">
                            <h4 class="text-sm font-medium mb-2 dark:text-slate-200">Category Summary</h4>
                            <div class="max-h-50 overflow-y-auto text-sm space-y-1">
                                <div v-for="item in registeredCategorySummary" :key="item.category_id" class="flex justify-between py-1 border-b last:border-0 dark:border-slate-800">
                                    <span class="text-muted-foreground">{{ item.category_name }}</span>
                                    <span class="font-medium dark:text-slate-200">{{ item.player_count }} players</span>
                                </div>
                                <div v-if="registeredCategorySummary.length === 0" class="text-muted-foreground italic text-center py-2">
                                    No players assigned to categories.
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center p-3 rounded-md bg-amber-50 text-amber-800 text-sm border border-amber-200 dark:bg-amber-900/10 dark:text-amber-200 dark:border-amber-800">
                            <AlertCircle class="h-4 w-4 mr-2" />
                            This will update the tournament details and registration list.
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isConfirmModalOpen = false">Cancel</Button>
                        <Button @click="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            Update Tournament
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Create Weight Category Modal -->
            <Dialog v-model:open="isCreateCategoryOpen">
                <DialogContent class="sm:max-w-106.25 dark:bg-slate-950 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-slate-100">Add Weight Category</DialogTitle>
                        <DialogDescription class="dark:text-slate-400">
                            Create a new weight category for {{ selectedGender }} - {{ getCategoryById(selectedAgeCategoryId!)?.name }}.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label htmlFor="name" class="dark:text-slate-300">Name</Label>
                            <Input
                                id="name"
                                v-model="createCategoryForm.name"
                                placeholder="e.g. -60, +70, 60-66"
                                class="col-span-3 dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                @keydown.enter="submitCreateWeightCategory"
                            />
                        </div>
                        <div v-if="createCategoryForm.error" class="text-sm text-destructive flex items-center gap-2">
                            <AlertCircle class="h-4 w-4" />
                            {{ createCategoryForm.error }}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isCreateCategoryOpen = false" class="dark:border-slate-800 dark:text-slate-300">
                            Cancel
                        </Button>
                        <Button @click="submitCreateWeightCategory" :disabled="isCreateCategoryLoading">
                            <Loader2 v-if="isCreateCategoryLoading" class="mr-2 h-4 w-4 animate-spin" />
                            <Plus v-else class="mr-2 h-4 w-4" />
                            Create Category
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Weight Category Modal -->
            <Dialog v-model:open="isDeleteCategoryOpen">
                <DialogContent class="sm:max-w-106.25 dark:bg-slate-950 dark:border-slate-800">
                    <DialogHeader>
                        <DialogTitle class="dark:text-slate-100">Delete Weight Category</DialogTitle>
                        <DialogDescription class="dark:text-slate-400">
                            Are you sure you want to delete this weight category? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="py-4">
                        <div class="rounded-md bg-destructive/10 p-4 border border-destructive/20">
                            <div class="flex">
                                <AlertCircle class="h-5 w-5 text-destructive mr-3" />
                                <div class="text-sm text-destructive-foreground">
                                    <p class="font-medium">Warning</p>
                                    <p class="mt-1">
                                        You are about to delete <strong>{{ getCategoryById(selectedCategoryId!)?.name }}</strong>.
                                        Any players assigned to this category will be unassigned.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div v-if="deleteCategoryError" class="mt-4 text-sm text-destructive flex items-center gap-2">
                            <AlertCircle class="h-4 w-4" />
                            {{ deleteCategoryError }}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" @click="isDeleteCategoryOpen = false" class="dark:border-slate-800 dark:text-slate-300">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="submitDeleteWeightCategory" :disabled="isDeleteCategoryLoading">
                            <Loader2 v-if="isDeleteCategoryLoading" class="mr-2 h-4 w-4 animate-spin" />
                            <Trash2 v-else class="mr-2 h-4 w-4" />
                            Delete Category
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
