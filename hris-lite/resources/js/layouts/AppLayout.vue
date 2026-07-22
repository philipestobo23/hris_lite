<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Banknote,
    Briefcase,
    Building2,
    CalendarCheck,
    CalendarDays,
    CalendarOff,
    ChartColumn,
    ChevronsUpDown,
    ClipboardList,
    Fingerprint,
    Network,
    Settings,
    ShieldCheck,
    SlidersHorizontal,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import BranchSwitcher from '@/components/BranchSwitcher.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCan } from '@/composables/useCan';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import type { BreadcrumbItem, NavItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = computed(() => page.props.sidebarOpen ?? true);

const { isCurrentOrParentUrl } = useCurrentUrl();
const { getInitials } = useInitials();
const { can } = useCan();

const showAvatar = computed(() => !!user.value.avatar);

// Primary HRIS navigation. These point at route paths the app will expose;
// keep them as strings so the layout does not depend on generated route
// helpers that may not exist yet for every module.
const mainNavItems: NavItem[] = [
    { title: 'Employees', href: '/employees', icon: Users },
    { title: 'DTR', href: '/dtr', icon: CalendarCheck },
    { title: 'Biometric Devices', href: '/biometric-devices', icon: Fingerprint },
    { title: 'Attendance Logs', href: '/attendance-logs', icon: ClipboardList },
    { title: 'Holidays', href: '/holidays', icon: CalendarDays },
    { title: 'Leave', href: '/leaves', icon: CalendarOff },
    { title: 'Payroll', href: '/payroll', icon: Banknote },
    { title: 'Branches', href: '/branches', icon: Building2 },
    { title: 'Departments', href: '/departments', icon: Network },
    { title: 'Positions', href: '/positions', icon: Briefcase },
    { title: 'Reports', href: '/reports', icon: ChartColumn },
];

const settingsNavItem: NavItem = {
    title: 'Settings',
    href: editProfile(),
    icon: Settings,
};
</script>

<template>
    <SidebarProvider :default-open="sidebarOpen">
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" as-child>
                            <Link :href="dashboard()">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup class="px-2 py-0">
                    <SidebarGroupLabel>Main</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in mainNavItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="isCurrentOrParentUrl(item.href)"
                                :tooltip="item.title"
                            >
                                <Link :href="item.href">
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem v-if="can('roles.view')">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentOrParentUrl('/roles')"
                            tooltip="Roles & Permissions"
                        >
                            <Link href="/roles">
                                <ShieldCheck />
                                <span>Roles &amp; Permissions</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-if="can('settings.view')">
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentOrParentUrl('/app-settings')"
                            tooltip="App Settings"
                        >
                            <Link href="/app-settings">
                                <SlidersHorizontal />
                                <span>App Settings</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="
                                isCurrentOrParentUrl(settingsNavItem.href)
                            "
                            :tooltip="settingsNavItem.title"
                        >
                            <Link :href="settingsNavItem.href">
                                <component :is="settingsNavItem.icon" />
                                <span>{{ settingsNavItem.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>

        <SidebarInset>
            <header
                class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <SidebarTrigger class="-ml-1" />
                    <Separator
                        v-if="breadcrumbs.length > 0"
                        orientation="vertical"
                        class="mr-1 hidden h-4 sm:block"
                    />
                    <Breadcrumbs
                        v-if="breadcrumbs.length > 0"
                        :breadcrumbs="breadcrumbs"
                        class="hidden min-w-0 sm:flex"
                    />
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <BranchSwitcher />
                    <!-- Optional extra top-bar content per page. -->
                    <slot name="branch-switcher" />

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                class="h-9 gap-2 px-1.5 data-[state=open]:bg-accent"
                                data-test="user-menu-button"
                            >
                                <Avatar class="size-7 rounded-md">
                                    <AvatarImage
                                        v-if="showAvatar"
                                        :src="user.avatar!"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-md text-xs text-black dark:text-white"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span
                                    class="hidden max-w-40 truncate text-sm font-medium sm:inline"
                                >
                                    {{ user.name }}
                                </span>
                                <ChevronsUpDown
                                    class="hidden size-4 text-muted-foreground sm:block"
                                />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            class="w-56 rounded-lg"
                            align="end"
                            :side-offset="4"
                        >
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <main
                class="flex h-full flex-1 flex-col gap-4 overflow-x-hidden p-4"
            >
                <slot />
            </main>

            <Toaster />
        </SidebarInset>
    </SidebarProvider>
</template>
