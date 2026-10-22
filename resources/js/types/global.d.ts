import type { Auth } from '@/types/auth';
import type { Notifications } from '@/types/notifications';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            notifications: Notifications | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
