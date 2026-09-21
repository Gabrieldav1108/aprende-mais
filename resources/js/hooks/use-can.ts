import { usePage } from '@inertiajs/react';

/**
 * Whether the authenticated user may perform the given permission.
 * Admins are granted every permission on the server, so they always pass.
 */
export function useCan(permission: string): boolean {
    const { roles, permissions } = usePage().props.auth;

    return roles.includes('admin') || permissions.includes(permission);
}

export function useHasRole(role: string): boolean {
    return usePage().props.auth.roles.includes(role);
}
