<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Calendar, Save, ChevronDown, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

const imageInputRef = ref<HTMLInputElement | null>(null);

const clearImage = () => {
    form.image = null;
    imagePreview.value = props.event.image_path || null;
    if (imageInputRef.value) {
        imageInputRef.value.value = '';
    }
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

        <div class="flex flex-col gap-6 p-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-primary/10 flex items-center justify-center border border-primary/20">
                        <Calendar class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">Edit Event</h1>
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
                        class="bg-background hover:bg-muted text-foreground"
                    >
                        Cancel
                    </Button>
                    <Button @click="submit" :disabled="form.processing" class="bg-primary hover:bg-primary/90 text-primary-foreground">
                        <Save class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </div>

            <Card class="border shadow-sm bg-card text-card-foreground">
                <CardContent class="p-6 grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <div class="space-y-5">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Details</h2>
                            <p class="text-xs text-muted-foreground">Edit how this event appears on the public homepage.</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="title">Event Title</Label>
                            <Input
                                id="title"
                                v-model="form.title"
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
                            <Input
                                id="location"
                                v-model="form.location"
                                class="bg-background border-input"
                                @input="form.clearErrors('location')"
                            />
                            <p v-if="form.errors.location" class="text-xs text-destructive">{{ form.errors.location }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label for="start_date">Start Date</Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    v-model="form.start_date"
                                    class="bg-background border-input"
                                    @change="form.clearErrors('start_date')"
                                />
                                <p v-if="form.errors.start_date" class="text-xs text-destructive">{{ form.errors.start_date }}</p>
                            </div>
                            <div class="grid gap-2">
                                <Label for="end_date">End Date (optional)</Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    v-model="form.end_date"
                                    class="bg-background border-input"
                                    @change="form.clearErrors('end_date')"
                                />
                                <p v-if="form.errors.end_date" class="text-xs text-destructive">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" class="w-full justify-between bg-background border-input text-foreground capitalize font-normal">
                                        {{ form.status }}
                                        <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                                    </Button>
                                </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="min-w-0 w-[var(--reka-dropdown-menu-trigger-width)]">
                                    <DropdownMenuItem @click="form.status = 'draft'; form.clearErrors('status')" class="capitalize">Draft</DropdownMenuItem>
                                    <DropdownMenuItem @click="form.status = 'published'; form.clearErrors('status')" class="capitalize">Published</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
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
                                ref="imageInputRef"
                                class="h-10 w-full rounded-md border border-input bg-muted/30 px-3 py-2 text-sm shadow-sm text-foreground file:border-0 file:bg-transparent file:text-sm file:font-medium"
                            />
                            <p v-if="form.errors.image" class="text-xs text-destructive">{{ form.errors.image }}</p>
                        </div>
                        <div class="relative rounded-lg border border-border bg-muted/30 h-64 flex items-center justify-center overflow-hidden">
                            <img
                                v-if="imagePreview"
                                :src="imagePreview"
                                alt="Event image preview"
                                class="max-h-full max-w-full object-contain"
                            />
                            <div v-else class="text-xs text-muted-foreground uppercase tracking-widest">No image</div>
                            <Button
                                v-if="form.image"
                                variant="outline"
                                size="icon"
                                class="absolute top-2 right-2 h-7 w-7 rounded-full bg-background/80 hover:bg-background"
                                @click="clearImage"
                                title="Remove image"
                            >
                                <X class="h-4 w-4" />
                            </Button>
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
