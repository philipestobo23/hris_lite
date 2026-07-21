import { usePage } from '@inertiajs/vue3';

/**
 * Read the shared `auth.can` permission map. Values are resolved server-side
 * (Super Admin resolves to all-true via the Gate::before bypass), so the UI
 * only mirrors the server's authorization decisions — it never grants access.
 */
export function useCan() {
    const page = usePage();

    function can(permission: string): boolean {
        return page.props.auth.can?.[permission] === true;
    }

    function canAny(...permissions: string[]): boolean {
        return permissions.some((permission) => can(permission));
    }

    function canAll(...permissions: string[]): boolean {
        return permissions.every((permission) => can(permission));
    }

    return { can, canAny, canAll };
}
