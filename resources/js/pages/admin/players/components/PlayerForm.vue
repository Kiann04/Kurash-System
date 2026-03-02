<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { 
    User, 
    Calendar, 
    Users, 
    MapPin, 
    Mail, 
    Phone, 
    Save,
    AlertCircle
} from 'lucide-vue-next';

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
                        <Label for="gender">Gender</Label>
                        <div class="relative">
                            <select 
                                id="gender"
                                v-model="form.gender" 
                                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 appearance-none"
                            >
                                <option value="" disabled>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                            </div>
                        </div>
                        <p v-if="form.errors.gender" class="text-sm text-destructive">{{ form.errors.gender }}</p>
                    </div>
                    
                    <div class="space-y-2">
                        <Label for="birthday">Birthday</Label>
                        <div class="relative">
                            <Calendar class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input id="birthday" type="date" v-model="form.birthday" class="pl-9" />
                        </div>
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
                        <AlertCircle class="h-3 w-3 text-amber-500" />
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

                <div class="space-y-2 pt-2">
                    <Label for="registered_at">Registration Date</Label>
                    <Input id="registered_at" type="date" v-model="form.registered_at" />
                    <p v-if="form.errors.registered_at" class="text-sm text-destructive">{{ form.errors.registered_at }}</p>
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
