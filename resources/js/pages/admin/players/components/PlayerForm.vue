<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

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
  <form @submit.prevent="submit" class="border rounded-xl p-6 space-y-6">
    
    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium">{{ props.player ? 'Edit Player' : 'Add New Kurash Player' }}</h3>
            <p class="text-sm text-muted-foreground">{{ props.player ? 'Update the details of the player.' : 'Enter the details of the player to add them to the system.' }}</p>
        </div>

        <div class="grid gap-2">
            <Label for="full_name">Full Name</Label>
            <Input id="full_name" v-model="form.full_name" placeholder="John Doe" />
            <p v-if="form.errors.full_name" class="text-red-500 text-sm">{{ form.errors.full_name }}</p>
        </div>

        <div class="grid gap-2">
            <Label for="gender">Gender</Label>
            <div class="relative">
                <select 
                    v-model="form.gender" 
                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <option value="" disabled>Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <p v-if="form.errors.gender" class="text-red-500 text-sm">{{ form.errors.gender }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="birthday">Birthday</Label>
                <Input id="birthday" type="date" v-model="form.birthday" />
                <p v-if="form.errors.birthday" class="text-red-500 text-sm">{{ form.errors.birthday }}</p>
            </div>
            <div class="grid gap-2">
                <Label for="registered_at">Membership Start Date</Label>
                <Input id="registered_at" type="date" v-model="form.registered_at" />
                <p v-if="form.errors.registered_at" class="text-red-500 text-sm">{{ form.errors.registered_at }}</p>
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="club">Club</Label>
            <Input id="club" v-model="form.club" placeholder="Team Kurash" />
            <p v-if="form.errors.club" class="text-red-500 text-sm">{{ form.errors.club }}</p>
        </div>

        <div class="grid gap-2">
            <Label for="address">Address</Label>
            <Input id="address" v-model="form.address" placeholder="123 Main St, City" />
            <p v-if="form.errors.address" class="text-red-500 text-sm">{{ form.errors.address }}</p>
        </div>

        <div class="grid gap-2">
            <Label for="email">Email Address</Label>
            <Input id="email" type="email" v-model="form.email" placeholder="john.doe@example.com" />
            <p v-if="form.errors.email" class="text-red-500 text-sm">{{ form.errors.email }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="emergency_contact">Emergency Contact Name</Label>
                <Input id="emergency_contact" v-model="form.emergency_contact" placeholder="Jane Doe" />
                <p v-if="form.errors.emergency_contact" class="text-red-500 text-sm">{{ form.errors.emergency_contact }}</p>
            </div>
            <div class="grid gap-2">
                <Label for="emergency_contact_number">Emergency Contact Number</Label>
                <Input id="emergency_contact_number" v-model="form.emergency_contact_number" placeholder="+123456789" />
                <p v-if="form.errors.emergency_contact_number" class="text-red-500 text-sm">{{ form.errors.emergency_contact_number }}</p>
            </div>
        </div>
    </div>

    <Button type="submit" class="w-full" :disabled="form.processing">
        {{ props.player ? 'Update Player' : 'Add Player' }}
    </Button>

  </form>
</template>
