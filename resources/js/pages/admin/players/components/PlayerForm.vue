<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { 
    User, 
    Calendar, 
    Users, 
    MapPin, 
    Mail, 
    Phone, 
    Save,
    AlertCircle,
    ChevronDown,
    Check
} from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { watch } from 'vue';
import { addYears, parseISO, format, isValid } from 'date-fns';

const props = defineProps<{
  player?: {
    id: number;
    full_name: string;
    gender: string;
    birthday: string;
    club: string;
    address: string;
    email: string;
    emergency_contact: string;
    emergency_contact_number: string;
    registered_at: string;
    membership_start_date?: string;
    membership_expires_at?: string;
  };
}>();

const form = useForm({
  full_name: props.player?.full_name || '',
  gender: props.player?.gender || '',
  birthday: props.player?.birthday || '',
  club: props.player?.club || '',
  address: props.player?.address || '',
  email: props.player?.email || '',
  emergency_contact: props.player?.emergency_contact || '',
  emergency_contact_number: props.player?.emergency_contact_number || '',
  registered_at: props.player?.registered_at || new Date().toISOString().split('T')[0],
  membership_start_date: props.player?.membership_start_date || props.player?.registered_at || new Date().toISOString().split('T')[0],
  membership_expires_at: props.player?.membership_expires_at || '',
});

// Initialize expiry if missing
if (!form.membership_expires_at && form.membership_start_date) {
    const start = parseISO(form.membership_start_date);
    if (isValid(start)) {
        form.membership_expires_at = format(addYears(start, 1), 'yyyy-MM-dd');
    }
}

// Auto-update expiry when start date changes
watch(() => form.membership_start_date, (newStart) => {
    if (!newStart) return;
    const startDate = parseISO(newStart);
    if (isValid(startDate)) {
        form.membership_expires_at = format(addYears(startDate, 1), 'yyyy-MM-dd');
    }
});

function submit() {
  if (props.player) {
    form.put(route('admin.players.update', props.player.id));
  } else {
    form.post(route('admin.players.store'));
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Personal Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <User class="h-5 w-5 text-primary" />
                    Personal Information
                </CardTitle>
                <CardDescription>Basic details about the player.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="full_name">Full Name</Label>
                    <div class="relative">
                        <User class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="full_name" v-model="form.full_name" placeholder="John Doe" class="pl-9" />
                    </div>
                    <p v-if="form.errors.full_name" class="text-sm text-destructive">{{ form.errors.full_name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                    <Label>Gender</Label>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button 
                                variant="outline" 
                                class="w-full justify-between font-normal"
                            >
                                {{ form.gender || 'Select Gender' }}
                                <ChevronDown class="ml-2 h-4 w-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-[--radix-dropdown-menu-trigger-width]">
                            <DropdownMenuItem @click="form.gender = 'Male'" class="cursor-pointer">
                                Male
                                <Check v-if="form.gender === 'Male'" class="ml-auto h-4 w-4" />
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="form.gender = 'Female'" class="cursor-pointer">
                                Female
                                <Check v-if="form.gender === 'Female'" class="ml-auto h-4 w-4" />
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <p v-if="form.errors.gender" class="text-sm text-destructive">{{ form.errors.gender }}</p>
                </div>
                    
                    <div class="space-y-2">
                    <Label for="birthday">Birthday</Label>
                    <DatePicker id="birthday" v-model="form.birthday" />
                    <p v-if="form.errors.birthday" class="text-sm text-destructive">{{ form.errors.birthday }}</p>
                </div>
                </div>

                <div class="space-y-2">
                    <Label for="club">Club / Affiliation</Label>
                    <div class="relative">
                        <Users class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="club" v-model="form.club" placeholder="Team Kurash" class="pl-9" />
                    </div>
                    <p v-if="form.errors.club" class="text-sm text-destructive">{{ form.errors.club }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Contact Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <MapPin class="h-5 w-5 text-primary" />
                    Contact Details
                </CardTitle>
                <CardDescription>Address and emergency contact info.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="address">Address</Label>
                    <div class="relative">
                        <MapPin class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="address" v-model="form.address" placeholder="123 Main St, City" class="pl-9" />
                    </div>
                    <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="email">Email Address</Label>
                    <div class="relative">
                        <Mail class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input id="email" type="email" v-model="form.email" placeholder="john.doe@example.com" class="pl-9" />
                    </div>
                    <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                </div>

                <Separator class="my-2" />

                <div class="space-y-2">
                    <Label class="text-sm font-semibold flex items-center gap-1">
                        <AlertCircle class="h-3 w-3 text-accent" />
                        Emergency Contact
                    </Label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="emergency_contact" class="text-xs text-muted-foreground">Name</Label>
                            <Input id="emergency_contact" v-model="form.emergency_contact" placeholder="Contact Person" />
                            <p v-if="form.errors.emergency_contact" class="text-sm text-destructive">{{ form.errors.emergency_contact }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="emergency_contact_number" class="text-xs text-muted-foreground">Phone Number</Label>
                            <div class="relative">
                                <Phone class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input id="emergency_contact_number" v-model="form.emergency_contact_number" placeholder="+123456789" class="pl-9" />
                            </div>
                            <p v-if="form.errors.emergency_contact_number" class="text-sm text-destructive">{{ form.errors.emergency_contact_number }}</p>
                        </div>
                    </div>
                </div>

                <Separator class="my-2" />

                <div class="space-y-2">
                    <Label class="text-sm font-semibold flex items-center gap-1">
                        <Calendar class="h-3 w-3 text-primary" />
                        Membership Period
                    </Label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="membership_start_date" class="text-xs text-muted-foreground">Start Date</Label>
                            <DatePicker id="membership_start_date" v-model="form.membership_start_date" />
                            <p v-if="form.errors.membership_start_date" class="text-sm text-destructive">{{ form.errors.membership_start_date }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="membership_expires_at" class="text-xs text-muted-foreground">End Date</Label>
                            <DatePicker id="membership_expires_at" v-model="form.membership_expires_at" />
                            <p v-if="form.errors.membership_expires_at" class="text-sm text-destructive">{{ form.errors.membership_expires_at }}</p>
                        </div>
                    </div>
                </div>
            </CardContent>
            <CardFooter class="flex justify-end border-t pt-4">
                <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                    <Save class="mr-2 h-4 w-4" />
                    {{ props.player ? 'Update Player' : 'Save Player' }}
                </Button>
            </CardFooter>
        </Card>
    </div>
  </form>
</template>
