import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
    /** Desktop sidebar edge; use `right` when app layout is RTL (e.g. Farsi). */
    sidebarSide?: 'left' | 'right';
};

export function AppShell({
    children,
    variant = 'sidebar',
    sidebarSide = 'left',
}: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return (
        <SidebarProvider defaultOpen={isOpen} side={sidebarSide}>
            {children}
        </SidebarProvider>
    );
}
