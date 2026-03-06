<script setup lang="ts">
import { Plus, Trash2, Loader2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { route } from 'ziggy-js'
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
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import type { TournamentWeightCategory } from '@/types/tournament'

interface Props {
    weightCategories: TournamentWeightCategory[]
    modelValue: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void
    (e: 'update:weightCategories', value: TournamentWeightCategory[]): void
}>()

// Computed: Two-way binding for weight categories list
const localWeightCategories = computed({
    get: () => props.weightCategories,
    set: (value) => emit('update:weightCategories', value),
})

// Computed: Two-way binding for selected category ID
const selectedCategoryId = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
})

// Helper: Normalizes gender string to 'male' | 'female' | ''
const normalizeGender = (value: string | null | undefined): 'male' | 'female' | '' => {
    const normalized = String(value ?? '').trim().toLowerCase()
    if (normalized === 'male' || normalized === 'm') return 'male'
    if (normalized === 'female' || normalized === 'f') return 'female'
    return ''
}

// Computed: Extracts unique genders from available weight categories
const genderOptions = computed(() =>
    Array.from(
        new Set(
            localWeightCategories.value
                .map((category) => normalizeGender(category.gender))
                .filter((gender): gender is 'male' | 'female' => gender === 'male' || gender === 'female'),
        ),
    ),
)

// State: Selected gender for filtering (allow empty to clear)
const selectedGender = ref<'male' | 'female' | ''>(genderOptions.value[0] ?? 'male')

// Computed: Filters available age categories based on selected gender
const ageCategoryOptions = computed(() => {
    const map = new Map<number, string>()

    localWeightCategories.value
        .filter((category) => !selectedGender.value || normalizeGender(category.gender) === selectedGender.value)
        .forEach((category) => {
            map.set(category.age_category_id, category.age_category_name || `Age Category #${category.age_category_id}`)
        })

    return Array.from(map.entries())
        .map(([id, name]) => ({ id, name }))
        .sort((a, b) => a.id - b.id)
})

// State: Selected age category ID for filtering
const selectedAgeCategoryId = ref<number | null>(ageCategoryOptions.value[0]?.id ?? null)

// Computed: Filters weight categories based on selected gender and age category
const weightCategoryOptions = computed(() => {
    if (!selectedGender.value || !selectedAgeCategoryId.value) {
        return []
    }
    return localWeightCategories.value
        .filter((category) =>
            normalizeGender(category.gender) === selectedGender.value &&
            category.age_category_id === selectedAgeCategoryId.value,
        )
        .slice()
        .sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }))
})

// Watcher: Resets age category selection when gender changes
watch(selectedGender, () => {
    selectedAgeCategoryId.value = selectedGender.value ? (ageCategoryOptions.value[0]?.id ?? null) : null
})

// Watcher: Auto-selects the first weight category when filters change
watch([selectedGender, selectedAgeCategoryId], () => {
    selectedCategoryId.value = weightCategoryOptions.value[0]?.id ?? null
}, { immediate: true })

// Action: Clear all selections (gender, age category, weight category)
const clearSelection = () => {
    selectedGender.value = ''
    selectedAgeCategoryId.value = null
    selectedCategoryId.value = null
}

// Create Weight Category State
const isCreateCategoryOpen = ref(false)
const isCreateCategoryLoading = ref(false)
const createCategoryForm = ref({
    name: '',
    error: '',
})

// Delete Weight Category State
const isDeleteCategoryOpen = ref(false)
const isDeleteCategoryLoading = ref(false)
const deleteCategoryError = ref('')

// Helper: Retrieves CSRF token for API requests
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token ?? ''
}

// Action: Opens the create category modal with validation check
const openCreateCategoryModal = () => {
    if (!selectedGender.value || !selectedAgeCategoryId.value) {
        createCategoryForm.value.error = 'Please select Gender and Age Category first.'
    } else {
        createCategoryForm.value.error = ''
    }
    createCategoryForm.value.name = ''
    isCreateCategoryOpen.value = true
}

// Action: Submits the create category form
const submitCreateWeightCategory = async () => {
    const name = createCategoryForm.value.name.trim()
    if (!name) {
        createCategoryForm.value.error = 'Category name is required'
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
                name,
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
            localWeightCategories.value = [...localWeightCategories.value, newCategory]
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

// Action: Opens the delete category confirmation modal
const openDeleteCategoryModal = () => {
    if (!selectedCategoryId.value) return
    deleteCategoryError.value = ''
    isDeleteCategoryOpen.value = true
}

// Action: Submits the delete category request
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
</script>

<template>
    <Card class="shadow-sm border-border bg-card text-card-foreground h-125 flex flex-col">
        <CardHeader class="border-b bg-muted/50 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold text-foreground">Category Selection</CardTitle>
                    <CardDescription class="text-muted-foreground">Select weight category to manage.</CardDescription>
                </div>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs text-muted-foreground hover:bg-muted"
                    @click="clearSelection"
                    title="Clear Gender, Age, and Weight Category"
                >
                    Clear Selection
                </Button>
            </div>
        </CardHeader>
        <CardContent class="space-y-4 pt-6 flex-1 flex flex-col min-h-0">
            <!-- Gender Selection -->
            <div class="space-y-2">
                <Label class="text-xs font-semibold uppercase text-muted-foreground">Gender</Label>
                <Select v-model="selectedGender">
                    <SelectTrigger class="w-full bg-background border-input capitalize">
                        <SelectValue placeholder="Select Gender" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="gender in genderOptions"
                            :key="gender"
                            :value="gender"
                            class="capitalize"
                        >
                            {{ gender }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Separator />

            <!-- Age Category Selection -->
            <div class="space-y-2">
                <Label class="text-xs font-semibold uppercase text-muted-foreground">Age Category</Label>
                <Select v-model.number="selectedAgeCategoryId">
                    <SelectTrigger class="w-full bg-background border-input">
                        <SelectValue placeholder="Select Age Category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in ageCategoryOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Weight Category Selection -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label class="text-xs font-semibold uppercase text-muted-foreground">Weight Category</Label>
                </div>
                
                <div class="flex gap-2">
                    <Select v-model="selectedCategoryId">
                        <SelectTrigger class="w-full bg-background border-input">
                            <SelectValue placeholder="Select Weight Category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="category in weightCategoryOptions"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </SelectItem>
                            <div v-if="weightCategoryOptions.length === 0" class="p-2 text-xs text-muted-foreground text-center">
                                No categories found
                            </div>
                        </SelectContent>
                    </Select>
                    
                    <Button
                        variant="outline"
                        class="px-3 bg-background border-input hover:bg-muted"
                        @click="openCreateCategoryModal"
                        :disabled="!selectedGender || !selectedAgeCategoryId"
                        title="Add New Category"
                    >
                        <Plus class="h-4 w-4" />
                    </Button>
                </div>
                
                <div v-if="selectedCategoryId" class="pt-2 flex justify-end">
                     <Button
                        variant="ghost"
                        size="sm"
                        class="h-7 text-xs text-destructive hover:text-destructive hover:bg-destructive/10"
                        @click="openDeleteCategoryModal"
                    >
                        <Trash2 class="mr-1 h-3 w-3" />
                        Delete Category
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>

    <!-- Create Category Dialog -->
    <Dialog :open="isCreateCategoryOpen" @update:open="isCreateCategoryOpen = $event">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>Add Weight Category</DialogTitle>
                <DialogDescription class="text-muted-foreground">
                    Create a new weight category for {{ selectedGender }} - {{ ageCategoryOptions.find(o => o.id === selectedAgeCategoryId)?.name }}.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="category-name">Category Name</Label>
                    <Input
                        id="category-name"
                        v-model="createCategoryForm.name"
                        placeholder="e.g. -60kg, +100kg"
                        class="bg-background border-input"
                        @keyup.enter="submitCreateWeightCategory"
                    />
                    <p class="text-xs text-muted-foreground">
                        Format: "-60", "+100", or "60-66"
                    </p>
                </div>
                <div v-if="createCategoryForm.error" class="text-sm font-medium text-destructive flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-destructive"></span>
                    {{ createCategoryForm.error }}
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isCreateCategoryOpen = false">Cancel</Button>
                <Button @click="submitCreateWeightCategory" :disabled="isCreateCategoryLoading" class="bg-primary hover:bg-primary/90 text-primary-foreground">
                    <Loader2 v-if="isCreateCategoryLoading" class="mr-2 h-4 w-4 animate-spin" />
                    Create Category
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Delete Category Confirmation -->
    <Dialog :open="isDeleteCategoryOpen" @update:open="isDeleteCategoryOpen = $event">
        <DialogContent class="sm:max-w-106.25">
            <DialogHeader>
                <DialogTitle>Delete Weight Category</DialogTitle>
                <DialogDescription class="text-muted-foreground">
                    Are you sure you want to delete this weight category? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <div v-if="deleteCategoryError" class="py-2 text-sm font-medium text-destructive">
                {{ deleteCategoryError }}
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isDeleteCategoryOpen = false">Cancel</Button>
                <Button variant="destructive" @click="submitDeleteWeightCategory" :disabled="isDeleteCategoryLoading">
                    <Loader2 v-if="isDeleteCategoryLoading" class="mr-2 h-4 w-4 animate-spin" />
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
