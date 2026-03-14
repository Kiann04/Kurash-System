<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, MapPin, Image as ImageIcon, Edit } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

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

const getImageSrc = (path: string | null) => {
    if (!path) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) return path;
    return '/storage/' + path.replace(/^storage\//, '');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Event Details" />

        <div class="flex flex-col gap-6 p-6 lg:px-8">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                        <Calendar class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">{{ props.event.title }}</h1>
                        <p class="text-sm text-muted-foreground">Event details preview.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" as-child class="gap-2">
                        <Link :href="route('admin.events.edit', props.event.id)">
                            <Edit class="h-4 w-4" />
                            Edit Event
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Details -->
            <Card class="shadow-sm bg-card text-card-foreground border-border">
                <CardHeader class="border-b bg-muted/50">
                    <CardTitle class="flex items-center gap-3">
                        <Calendar class="h-5 w-5 text-primary" />
                        {{ formatDateRange(props.event.start_date, props.event.end_date) }}
                    </CardTitle>
                    <CardDescription class="flex items-center gap-2">
                        <MapPin class="h-4 w-4" />
                        <span>{{ props.event.location || 'Not specified' }}</span>
                        <Badge :class="['ml-3 capitalize shadow-none font-normal', getStatusColor(props.event.status)]">
                            {{ props.event.status }}
                        </Badge>
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Left: Image / Placeholder -->
                    <div class="rounded-lg border border-border bg-muted/30 h-64 xl:h-96 flex items-center justify-center overflow-hidden">
                        <img
                            v-if="props.event.image_path"
                            :src="getImageSrc(props.event.image_path)"
                            alt="Event image"
                            class="max-h-full max-w-full object-contain"
                        />
                        <div v-else class="flex flex-col items-center gap-2 text-xs text-muted-foreground uppercase tracking-widest">
                            <ImageIcon class="h-6 w-6" />
                            No image
                        </div>
                    </div>

                    <!-- Right: Description -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Description</h3>
                        <p class="text-sm text-muted-foreground leading-relaxed">
                            {{ props.event.description || 'No description provided.' }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
