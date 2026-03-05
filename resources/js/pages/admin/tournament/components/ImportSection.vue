<script setup lang="ts">
import { ref } from 'vue'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Loader2, Upload, AlertTriangle, CheckCircle2, FileText } from 'lucide-vue-next'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { ImportAnalysis, ImportRowResult } from '@/types/tournament'
import { route } from 'ziggy-js'

interface Props {
    csrfToken: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
    (e: 'imported', registrations: { player_id: number; tournament_weight_category_id: number }[]): void
}>()

// State: File import management
const importFile = ref<File | null>(null)
const importProcessing = ref(false)
const importError = ref<string | null>(null)
const importAnalysis = ref<ImportAnalysis | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

// Handler: Updates file state on selection change
const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    if (target.files && target.files.length > 0) {
        importFile.value = target.files[0]
        importError.value = null
        importAnalysis.value = null
    }
}

// Action: Triggers the hidden file input
const triggerFileInput = () => {
    fileInput.value?.click()
}

// Action: Analyzes the selected file via API before import
const analyzeAndImportFile = async () => {
    if (!importFile.value) {
        importError.value = 'Please select a file first.'
        return
    }

    importProcessing.value = true
    importError.value = null
    importAnalysis.value = null

    const formData = new FormData()
    formData.append('file', importFile.value)

    try {
        const response = await fetch(route('admin.tournaments.import-analysis', undefined, false), {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': props.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            const message = errorData?.message ?? 'Failed to analyze file.'
            throw new Error(message)
        }

        const data = await response.json()
        importAnalysis.value = data.analysis
    } catch (error) {
        const message = error instanceof Error ? error.message : 'Failed to analyze file.'
        importError.value = message
    } finally {
        importProcessing.value = false
    }
}

// Action: Confirms import of matched registrations
const confirmImport = () => {
    if (!importAnalysis.value) return

    const newRegistrations = importAnalysis.value.registrations.map((reg) => ({
        player_id: reg.player_id,
        tournament_weight_category_id: reg.tournament_weight_category_id,
    }))

    emit('imported', newRegistrations)
    
    // Reset state
    importFile.value = null
    importAnalysis.value = null
    importError.value = null
}

// Helper: Determines badge variant based on import row status
const getStatusBadgeVariant = (status: ImportRowResult['status']) => {
    switch (status) {
        case 'matched': return 'default' // primary
        case 'unmatched_player': return 'destructive'
        case 'unresolved_category': return 'secondary' // warning -> secondary
        case 'duplicate': return 'secondary'
        default: return 'outline'
    }
}
</script>

<template>
    <Card class="shadow-sm border-slate-200 dark:bg-slate-950 dark:border-slate-800">
        <CardHeader class="border-b bg-slate-50/50 dark:bg-slate-900/50 dark:border-slate-800 pb-4">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold text-slate-900 dark:text-slate-100">Batch Import</CardTitle>
                    <CardDescription>Upload a CSV/Excel/DOCX file to register players in bulk.</CardDescription>
                </div>
                <Button variant="outline" size="sm" as-child>
                    <a :href="route('admin.tournaments.download-template')">
                        <FileText class="w-4 h-4 mr-2" />
                        Download Template
                    </a>
                </Button>
            </div>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="grid w-full max-w-sm items-center gap-1.5">
                    <Label for="file" class="dark:text-slate-300">Registration File</Label>
                    <div class="relative">
                        <input
                            id="file"
                            ref="fileInput"
                            type="file"
                            accept=".csv,.xlsx,.xls,.docx"
                            @change="handleFileChange"
                            class="hidden"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="triggerFileInput"
                            class="w-full justify-start text-left font-normal"
                            :class="{'text-slate-500 dark:text-slate-400': !importFile, 'text-slate-900 dark:text-slate-100': importFile}"
                        >
                            <FileText class="mr-2 h-4 w-4" />
                            <span class="truncate">{{ importFile ? importFile.name : 'Select File...' }}</span>
                        </Button>
                    </div>
                </div>
                <Button
                    @click="analyzeAndImportFile"
                    :disabled="!importFile || importProcessing"
                    class="w-full sm:w-auto dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:text-white"
                >
                    <Loader2 v-if="importProcessing" class="mr-2 h-4 w-4 animate-spin" />
                    <Upload v-else class="mr-2 h-4 w-4" />
                    Analyze File
                </Button>
            </div>

            <Alert v-if="importError" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ importError }}</AlertDescription>
            </Alert>

            <div v-if="importAnalysis" class="space-y-4 border rounded-md p-4 bg-slate-50 dark:bg-slate-900/50 dark:border-slate-800">
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="p-3 bg-white rounded shadow-sm border dark:bg-slate-950 dark:border-slate-800">
                        <div class="text-xs text-slate-500 uppercase font-bold dark:text-slate-400">Total Rows</div>
                        <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ importAnalysis.total_rows }}</div>
                    </div>
                    <div class="p-3 bg-white rounded shadow-sm border dark:bg-slate-950 dark:border-slate-800">
                        <div class="text-xs text-emerald-600 uppercase font-bold dark:text-emerald-500">Matched</div>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-500">{{ importAnalysis.matched_count }}</div>
                    </div>
                    <div class="p-3 bg-white rounded shadow-sm border dark:bg-slate-950 dark:border-slate-800">
                        <div class="text-xs text-amber-600 uppercase font-bold dark:text-amber-500">Issues</div>
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-500">
                            {{ importAnalysis.unmatched_player_count + importAnalysis.unresolved_category_count }}
                        </div>
                    </div>
                    <div class="p-3 bg-white rounded shadow-sm border dark:bg-slate-950 dark:border-slate-800">
                        <div class="text-xs text-slate-500 uppercase font-bold dark:text-slate-400">Duplicates</div>
                        <div class="text-2xl font-bold text-slate-700 dark:text-slate-300">{{ importAnalysis.duplicate_count }}</div>
                    </div>
                </div>

                <div class="max-h-60 overflow-y-auto border rounded bg-white dark:bg-slate-950 dark:border-slate-800">
                    <Table>
                        <TableHeader class="bg-slate-50 sticky top-0 dark:bg-slate-900/80">
                            <TableRow class="dark:border-slate-800">
                                <TableHead class="w-20 dark:text-slate-400">Row</TableHead>
                                <TableHead class="dark:text-slate-400">Status</TableHead>
                                <TableHead class="dark:text-slate-400">Player</TableHead>
                                <TableHead class="dark:text-slate-400">Category</TableHead>
                                <TableHead class="dark:text-slate-400">Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in importAnalysis.rows" :key="row.row" class="dark:border-slate-800">
                                <TableCell class="font-mono text-xs dark:text-slate-400">#{{ row.row }}</TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(row.status)" class="uppercase text-[10px]">
                                        {{ row.status.replace('_', ' ') }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="dark:text-slate-300">{{ row.player }}</TableCell>
                                <TableCell class="dark:text-slate-300">{{ row.category || '-' }}</TableCell>
                                <TableCell class="text-xs text-slate-500 dark:text-slate-400">{{ row.reason }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex justify-end pt-2">
                    <Button 
                        @click="confirmImport" 
                        :disabled="importAnalysis.matched_count === 0"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-600 dark:hover:bg-emerald-700"
                    >
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        Import {{ importAnalysis.matched_count }} Registrations
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
