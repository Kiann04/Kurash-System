<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface EventItem {
    id: number;
    title: string;
    location: string | null;
    start_date: string;
    end_date: string | null;
    status: string;
    image_path: string | null;
}

const props = defineProps<{
    events: {
        data: EventItem[];
        links: any[];
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: route('admin.events.index') },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};

const formatDateRange = (start: string, end: string | null) => {
    if (!end || end === start) {
        return formatDate(start);
    }

    return `${formatDate(start)} - ${formatDate(end)}`;
};

const getStatusColor = (status: string) => {
    return status === 'published'
        ? 'bg-primary/15 text-primary hover:bg-primary/25 border-primary/20'
        : 'bg-muted text-muted-foreground hover:bg-muted/80 border-border';
};

const confirmDelete = (id: number) => {
    if (!confirm('Delete this event?')) return;

    router.delete(route('admin.events.destroy', id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Events" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                        <Calendar class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Event Management</h1>
                        <p class="text-sm text-muted-foreground">
                            Create and manage public events.
                        </p>
                    </div>
                </div>

                <Button as-child class="gap-2 shadow-sm bg-primary hover:bg-primary/90 text-primary-foreground">
                    <Link :href="route('admin.events.create')">
                        <Plus class="h-4 w-4" />
                        Create Event
                    </Link>
                </Button>
            </div>

            <Card class="border-none bg-transparent shadow-none">
                <CardContent class="p-0">
                    <div class="relative w-full overflow-auto rounded-md border border-border">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/50 hover:bg-muted/50">
                                <TableHead class="h-12 px-4 align-middle font-semibold text-muted-foreground">Event</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-muted-foreground">Location</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-muted-foreground">Dates</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-center text-muted-foreground">Status</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-right text-muted-foreground">Actions</TableHead>
                            </TableRow>
                            </TableHeader>
                            <TableBody>
                            <TableRow
                                v-for="event in props.events.data"
                                :key="event.id"
                                    class="hover:bg-muted/50 transition-colors border-b border-border"
                            >
                                <TableCell class="p-4 align-middle font-medium">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-lg bg-muted flex items-center justify-center text-muted-foreground border border-border">
                                            <Calendar class="h-4.5 w-4.5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-foreground font-semibold">{{ event.title }}</span>
                                            <span class="text-xs text-muted-foreground font-mono">ID: #{{ event.id }}</span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="p-4 align-middle">
                                    <span class="text-muted-foreground">{{ event.location || '-' }}</span>
                                </TableCell>
                                <TableCell class="p-4 align-middle">
                                    <span class="text-muted-foreground font-medium text-sm">
                                        {{ formatDateRange(event.start_date, event.end_date) }}
                                    </span>
                                </TableCell>
                                <TableCell class="p-4 align-middle text-center">
                                    <Badge :class="['capitalize shadow-sm font-medium border-transparent', getStatusColor(event.status)]">
                                        {{ event.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="p-4 align-middle text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                            class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground hover:bg-muted rounded-full"
                                            title="Edit Event"
                                        >
                                            <Link :href="route('admin.events.edit', event.id)">
                                                <Edit class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            as-child
                                            class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground hover:bg-muted rounded-full"
                                            title="View Event"
                                        >
                                            <Link :href="route('admin.events.show', event.id)">
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-full"
                                            @click="confirmDelete(event.id)"
                                            title="Delete Event"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="props.events.data.length === 0">
                                <TableCell colspan="5" class="p-8 text-center text-muted-foreground">
                                    No events found. Create one to get started.
                                </TableCell>
                            </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div class="mt-4">
                        <Pagination :links="props.events.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
