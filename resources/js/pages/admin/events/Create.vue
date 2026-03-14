<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Save } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Events', href: route('admin.events.index') },
    { title: 'Create Event', href: '' },
];

const form = useForm({
    title: '',
    description: '',
    location: '',
    start_date: '',
    end_date: '',
    status: 'draft',
    image: null as File | null,
});

const onImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.image = target.files && target.files[0] ? target.files[0] : null;
};

const getImageUrl = (file: File) => {
    return URL.createObjectURL(file);
};

const submit = () => {
    form.post(route('admin.events.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Event" />

        <div class="flex flex-col gap-6 p-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                        <Calendar class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Create Event</h1>
                        <p class="text-sm text-muted-foreground">
                            Add a new public event for the homepage.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        :href="route('admin.events.index')"
                        as="a"
                        class="bg-background hover:bg-muted text-foreground"
                    >
                        Cancel
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="bg-primary hover:bg-primary/90 text-primary-foreground">
                        <Save class="mr-2 h-4 w-4" />
                        Save Event
                    </Button>
                </div>
            </div>

            <Card class="border shadow-sm bg-card text-card-foreground">
                <CardContent class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Details</h2>
                            <p class="text-xs text-muted-foreground">Create a new event for the public homepage.</p>
                        </div>
                    <div class="grid gap-2">
                        <Label for="title">Event Title</Label>
                        <Input
                            id="title"
                            v-model="form.title"
                            placeholder="e.g. National Kurash Championship"
                            class="bg-background border-input"
                            @input="form.clearErrors('title')"
                        />
                        <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="description">Description (optional)</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Short event summary or slogan."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="location">Location</Label>
                        <Input id="location" v-model="form.location" placeholder="e.g. Manila Sports Complex" class="bg-background border-input" />
                        <p v-if="form.errors.location" class="text-xs text-destructive">{{ form.errors.location }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date</Label>
                            <DatePicker id="start_date" v-model="form.start_date" class="bg-background border-input" />
                            <p v-if="form.errors.start_date" class="text-xs text-destructive">{{ form.errors.start_date }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_date">End Date (optional)</Label>
                            <DatePicker id="end_date" v-model="form.end_date" class="bg-background border-input" />
                            <p v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                    </div>
                    </div>

                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Media</h2>
                            <p class="text-xs text-muted-foreground">Upload a poster image for the homepage cards and modal.</p>
                        </div>
                    <div class="grid gap-2">
                        <Label for="image">Event Image (optional)</Label>
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
                            @change="onImageChange"
                            class="h-10 w-full rounded-md border border-input bg-muted/30 px-3 py-2 text-sm shadow-sm text-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium"
                        />
                        <p v-if="form.errors.image" class="text-xs text-destructive">{{ form.errors.image }}</p>
                    </div>
                    <div class="rounded-lg border border-border bg-muted/30 h-64 flex items-center justify-center overflow-hidden">
                        <img
                            v-if="form.image"
                            :src="getImageUrl(form.image)"
                            alt="Event image preview"
                            class="max-h-full max-w-full object-contain"
                        />
                        <div v-else class="text-xs text-muted-foreground uppercase tracking-widest">No image</div>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        Recommended size: 1600×900 or larger. JPG/PNG, max 2MB.
                    </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
