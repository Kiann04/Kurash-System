<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface EventItem {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_date: string;
    end_date: string | null;
    status: string;
    image_path: string | null;
}

const props = defineProps<{
    event: EventItem;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: route('admin.events.index') },
    { title: 'Event Details', href: '' },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Event Details" />

        <div class="flex flex-col gap-6 p-6 max-w-4xl">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">
                    <Calendar class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ props.event.title }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Event details preview.
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-slate-500">Dates</div>
                        <div class="text-slate-900 dark:text-slate-100 font-semibold">
                            {{ formatDate(props.event.start_date) }}
                            <span v-if="props.event.end_date"> - {{ formatDate(props.event.end_date) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-slate-500">Description</div>
                        <div class="text-slate-900 dark:text-slate-100">
                            {{ props.event.description || 'No description' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-slate-500">Location</div>
                        <div class="text-slate-900 dark:text-slate-100 font-semibold">
                            {{ props.event.location || 'Not specified' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-slate-500">Status</div>
                        <div class="text-slate-900 dark:text-slate-100 font-semibold capitalize">
                            {{ props.event.status }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-slate-500">Image</div>
                        <div class="text-slate-600 dark:text-slate-400">
                            {{ props.event.image_path || 'No image set' }}
                        </div>
                    </div>
                </div>

                <div v-if="props.event.image_path" class="pt-4">
                    <img :src="props.event.image_path" alt="Event image" class="w-full max-h-80 object-cover rounded-lg border border-slate-200 dark:border-slate-800" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
