<script setup lang="ts">
import { Building2, Check, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useBranch } from '@/composables/useBranch';

const { active, accessible, canSwitchAll, activeBranch, switchTo } = useBranch();

const label = computed(() => activeBranch.value?.name ?? 'All branches');
// Only offer the dropdown when there's more than one choice.
const hasChoice = computed(
    () => accessible.value.length > 1 || canSwitchAll.value,
);
</script>

<template>
    <div v-if="accessible.length > 0">
        <!-- Fixed to a single branch: show it, no dropdown. -->
        <div
            v-if="!hasChoice"
            class="flex h-9 items-center gap-2 rounded-md border px-2.5 text-sm text-muted-foreground"
        >
            <Building2 class="size-4" />
            <span class="max-w-32 truncate">{{ label }}</span>
        </div>

        <DropdownMenu v-else>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" size="sm" class="h-9 gap-2">
                    <Building2 class="size-4 text-muted-foreground" />
                    <span class="max-w-32 truncate">{{ label }}</span>
                    <ChevronsUpDown class="size-4 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <DropdownMenuLabel>Switch branch</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    v-if="canSwitchAll"
                    class="justify-between"
                    @select="switchTo(null)"
                >
                    All branches
                    <Check v-if="active === null" class="size-4" />
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="option in accessible"
                    :key="option.id"
                    class="justify-between"
                    @select="switchTo(option.id)"
                >
                    <span class="truncate">{{ option.name }}</span>
                    <Check v-if="active === option.id" class="size-4" />
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
