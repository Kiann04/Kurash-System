<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    { title: 'Edit Event', href: '' },
];

const toDateInput = (value: string | null) => {
    if (!value) return '';
    if (value.includes('T')) return value.split('T')[0];
    return value;
};

const form = useForm({
    _method: 'put',
    title: props.event.title || '',
    description: props.event.description || '',
    location: props.event.location || '',
    start_date: toDateInput(props.event.start_date),
    end_date: toDateInput(props.event.end_date),
    status: props.event.status || 'draft',
    image: null as File | null,
});

const imagePreview = ref<string | null>(props.event.image_path || null);

const onImageChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files && target.files[0] ? target.files[0] : null;
    form.image = file;
    imagePreview.value = file ? URL.createObjectURL(file) : props.event.image_path || null;
};

const submit = () => {
    form.post(route('admin.events.update', props.event.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Edit Event" />

        <div class="flex flex-col gap-6 p-6 max-w-5xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-100 dark:bg-amber-900/20 dark:border-amber-800">
                        <Calendar class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Edit Event</h1>
                        <p class="text-sm text-muted-foreground">
                            Update event details shown on the homepage.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        :href="route('admin.events.index')"
                        as="a"
                        class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        Cancel
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="bg-amber-600 hover:bg-amber-700 text-white">
                        <Save class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </div>

            <Card class="border shadow-sm bg-white dark:bg-slate-950 dark:border-slate-800">
                <CardContent class="p-6 md:grid md:grid-cols-2 md:gap-8 space-y-6 md:space-y-0">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Details</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Edit how this event appears on the public homepage.</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="title" class="dark:text-slate-300">Event Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
                                class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                @input="form.clearErrors('title')"
                            />
                            <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="description" class="dark:text-slate-300">Description (optional)</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                placeholder="Short event summary or slogan."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="location" class="dark:text-slate-300">Location</Label>
                            <Input
                                id="location"
                                v-model="form.location"
                                class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                @input="form.clearErrors('location')"
                            />
                            <p v-if="form.errors.location" class="text-xs text-red-500">{{ form.errors.location }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="start_date" class="dark:text-slate-300">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                    @change="form.clearErrors('start_date')"
                                />
                                <p v-if="form.errors.start_date" class="text-xs text-red-500">{{ form.errors.start_date }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date" class="dark:text-slate-300">End Date (optional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    v-model="form.end_date"
                                    class="dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                    @change="form.clearErrors('end_date')"
                                />
                                <p v-if="form.errors.end_date" class="text-xs text-red-500">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="status" class="dark:text-slate-300">Status</Label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                                @change="form.clearErrors('status')"
                            >
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <p v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Media</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Upload a poster image for the homepage cards and modal.</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="image" class="dark:text-slate-300">Event Image (optional)</Label>
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                @change="onImageChange"
                                class="h-10 w-full rounded-md border border-input bg-slate-50 px-3 py-2 text-sm shadow-sm dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100"
                            />
                            <p v-if="form.errors.image" class="text-xs text-red-500">{{ form.errors.image }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/40 h-64 flex items-center justify-center overflow-hidden">
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                alt="Event image preview"
                                class="max-h-full max-w-full object-contain"
                            />
                            <div v-else class="text-xs text-slate-500 uppercase tracking-widest">No image</div>
                        </div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            Recommended size: 1600×900 or larger. JPG/PNG, max 2MB.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
