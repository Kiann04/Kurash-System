<script setup lang="ts">
import { Loader2, Upload, AlertTriangle, CheckCircle2, FileText, Maximize2 } from 'lucide-vue-next'
import { ref } from 'vue'
import { route } from 'ziggy-js'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Label } from '@/components/ui/label'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import type { ImportAnalysis, ImportRowResult } from '@/types/tournament'

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
const previewOpen = ref(false)

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
    <Card class="shadow-sm border-border bg-card text-card-foreground">
        <CardHeader class="border-b bg-muted/50 pb-4">
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="text-base font-semibold text-foreground">Batch Import</CardTitle>
                    <CardDescription class="text-muted-foreground">Upload a CSV/Excel/DOCX file to register players in bulk.</CardDescription>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm">
                            <FileText class="w-4 h-4 mr-2" />
                            Download Template
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent>
                        <DropdownMenuItem as-child>
                            <a :href="route('admin.tournaments.download-template', { format: 'csv' })">
                                Excel Template (.csv)
                            </a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="route('admin.tournaments.download-template', { format: 'docx' })">
                                Word Template (.docx)
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </CardHeader>
        <CardContent class="space-y-4 pt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="grid w-full max-w-sm items-center gap-1.5">
                    <Label for="file">Registration File</Label>
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
                            :class="{'text-muted-foreground': !importFile, 'text-foreground': importFile}"
                        >
                            <FileText class="mr-2 h-4 w-4" />
                            <span class="truncate">{{ importFile ? importFile.name : 'Select File...' }}</span>
                        </Button>
                    </div>
                </div>
                <Button
                    @click="analyzeAndImportFile"
                    :disabled="!importFile || importProcessing"
                    class="w-full sm:w-auto bg-primary text-primary-foreground hover:bg-primary/90"
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

            <div v-if="importAnalysis" class="space-y-4 border border-border rounded-md p-4 bg-muted/30">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-foreground">Registration Preview</div>
                    <Button variant="outline" size="sm" @click="previewOpen = true">
                        <Maximize2 class="mr-2 h-4 w-4" />
                        Fullscreen
                    </Button>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="p-3 bg-card rounded shadow-sm border border-border">
                        <div class="text-xs text-muted-foreground uppercase font-bold">Total Rows</div>
                        <div class="text-2xl font-bold text-foreground">{{ importAnalysis.total_rows }}</div>
                    </div>
                    <div class="p-3 bg-card rounded shadow-sm border border-border">
                        <div class="text-xs text-primary uppercase font-bold">Matched</div>
                        <div class="text-2xl font-bold text-primary">{{ importAnalysis.matched_count }}</div>
                    </div>
                    <div class="p-3 bg-card rounded shadow-sm border border-border">
                        <div class="text-xs text-destructive uppercase font-bold">Issues</div>
                        <div class="text-2xl font-bold text-destructive">
                            {{ importAnalysis.unmatched_player_count + importAnalysis.unresolved_category_count }}
                        </div>
                    </div>
                    <div class="p-3 bg-card rounded shadow-sm border border-border">
                        <div class="text-xs text-muted-foreground uppercase font-bold">Duplicates</div>
                        <div class="text-2xl font-bold text-foreground">{{ importAnalysis.duplicate_count }}</div>
                    </div>
                </div>

                <div class="max-h-60 overflow-y-auto border border-border rounded bg-card">
                    <Table>
                        <TableHeader class="bg-muted/50 sticky top-0">
                            <TableRow class="border-border">
                                <TableHead class="w-20">Row</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Player</TableHead>
                                <TableHead>Category</TableHead>
                                <TableHead>Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in importAnalysis.rows" :key="row.row" class="border-border">
                                <TableCell class="font-mono text-xs text-muted-foreground">#{{ row.row }}</TableCell>
                                <TableCell>
                                    <Badge :variant="getStatusBadgeVariant(row.status)" class="uppercase text-[10px]">
                                        {{ row.status.replace('_', ' ') }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-foreground">{{ row.player }}</TableCell>
                                <TableCell class="text-foreground">{{ row.category || '-' }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ row.reason }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex justify-end pt-2">
                    <Button 
                        @click="confirmImport" 
                        :disabled="importAnalysis.matched_count === 0"
                        class="bg-primary hover:bg-primary/90 text-primary-foreground"
                    >
                        <CheckCircle2 class="mr-2 h-4 w-4" />
                        Import {{ importAnalysis.matched_count }} Registrations
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>

    <Dialog v-model:open="previewOpen">
        <DialogContent class="w-[98vw] sm:max-w-[92vw] md:max-w-[96vw] lg:max-w-[1400px] xl:max-w-[1600px]">
            <DialogHeader>
                <DialogTitle>Registration File Preview</DialogTitle>
            </DialogHeader>
            <div class="max-h-[75vh] w-full overflow-y-auto overflow-x-hidden border border-border rounded bg-card">
                <Table>
                    <TableHeader class="bg-muted/50 sticky top-0">
                        <TableRow class="border-border">
                            <TableHead class="w-20">Row</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Player</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Details</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in importAnalysis?.rows" :key="row.row" class="border-border">
                            <TableCell class="font-mono text-xs text-muted-foreground">#{{ row.row }}</TableCell>
                            <TableCell>
                                <Badge :variant="getStatusBadgeVariant(row.status)" class="uppercase text-[10px]">
                                    {{ row.status.replace('_', ' ') }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-foreground">{{ row.player }}</TableCell>
                            <TableCell class="text-foreground">{{ row.category || '-' }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ row.reason }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </DialogContent>
    </Dialog>
</template>
