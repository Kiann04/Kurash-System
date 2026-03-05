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
        ? 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50'
        : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50';
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
                    <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">
                        <Calendar class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Event Management</h1>
                        <p class="text-sm text-muted-foreground">
                            Create and manage public events.
                        </p>
                    </div>
                </div>

                <Button as-child class="gap-2 shadow-sm bg-amber-600 hover:bg-amber-700 text-white">
                    <Link :href="route('admin.events.create')">
                        <Plus class="h-4 w-4" />
                        Create Event
                    </Link>
                </Button>
            </div>

            <Card class="border shadow-sm bg-white dark:bg-slate-950 dark:border-slate-800 overflow-hidden">
                <CardContent class="p-0">
                    <Table>
                        <TableHeader class="bg-slate-50/50 dark:bg-slate-900/50 sticky top-0 z-10 backdrop-blur-sm">
                            <TableRow class="hover:bg-transparent dark:hover:bg-transparent border-b dark:border-slate-800">
                                <TableHead class="h-12 px-4 align-middle font-semibold text-slate-500 dark:text-slate-400">Event</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-slate-500 dark:text-slate-400">Location</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-slate-500 dark:text-slate-400">Dates</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-center text-slate-500 dark:text-slate-400">Status</TableHead>
                                <TableHead class="h-12 px-4 align-middle font-semibold text-right text-slate-500 dark:text-slate-400">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="event in props.events.data"
                                :key="event.id"
                                class="hover:bg-slate-50/50 transition-colors dark:hover:bg-slate-900/50 dark:border-slate-800 border-b last:border-0"
                            >
                                <TableCell class="p-4 align-middle font-medium">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                            <Calendar class="h-4.5 w-4.5" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-slate-900 font-semibold dark:text-slate-100">{{ event.title }}</span>
                                            <span class="text-xs text-slate-500 font-mono dark:text-slate-500">ID: #{{ event.id }}</span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="p-4 align-middle">
                                    <span class="text-slate-600 dark:text-slate-400">{{ event.location || '-' }}</span>
                                </TableCell>
                                <TableCell class="p-4 align-middle">
                                    <span class="text-slate-600 dark:text-slate-400 font-medium text-sm">
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
                                            class="h-8 w-8 p-0 text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:text-slate-400 dark:hover:text-amber-400 dark:hover:bg-amber-900/30 rounded-full"
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
                                            class="h-8 w-8 p-0 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:text-slate-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/30 rounded-full"
                                            title="View Event"
                                        >
                                            <Link :href="route('admin.events.show', event.id)">
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 p-0 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:text-slate-400 dark:hover:text-red-400 dark:hover:bg-red-900/30 rounded-full"
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
                    <div class="mt-4">
                        <Pagination :links="props.events.links" />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
