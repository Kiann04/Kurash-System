<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, LayoutGrid, Brackets, Users, Trophy, Calendar, Settings } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Membership Registration',
        href: route('admin.players.index'),
        icon: Users,
    },
    {
        title: 'Player Profiles',
        href: route('admin.player-details.index'),
        icon: Users,
    },
    {
        title: 'Tournaments',
        href: route('admin.tournaments.index'),
        icon: Trophy,
    },
    {
        title: 'Brackets',
        href: route('admin.brackets.index'),
        icon: Brackets,
    },
    {
        title: 'Events',
        href: route('admin.events.index'),
        icon: Calendar,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'System Settings',
        href: '#', // Placeholder
        icon: Settings,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="md:h-16 md:py-2 group-data-[collapsible=icon]:p-2!">
                        <Link :href="dashboard()">
                            <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-transparent">
                                <img src="/images/ksf-logo.png" alt="KSF Logo" class="size-9 object-contain rounded-md" />
                            </div>
                            <div class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                <span class="truncate font-bold text-primary">Kurash System</span>
                                <span class="truncate text-xs text-muted-foreground">Admin Portal</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
    <slot />
</template>
