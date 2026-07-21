import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BranchOption } from '@/types';

/**
 * Reads the shared `branch` prop (accessible branches + active branch) and
 * exposes a switcher. Switching posts to the server, which persists the choice
 * and re-scopes the data on reload.
 */
export function useBranch() {
    const page = usePage();

    const branch = computed(() => page.props.branch);
    const active = computed(() => branch.value.active);
    const accessible = computed<BranchOption[]>(() => branch.value.accessible);
    const canSwitchAll = computed(() => branch.value.canSwitchAll);

    const activeBranch = computed<BranchOption | null>(
        () => accessible.value.find((b) => b.id === active.value) ?? null,
    );

    function switchTo(branchId: number | null): void {
        if (branchId === active.value) {
            return;
        }
        router.post(
            '/active-branch',
            { branch_id: branchId },
            { preserveScroll: true, preserveState: false },
        );
    }

    return { branch, active, accessible, canSwitchAll, activeBranch, switchTo };
}
