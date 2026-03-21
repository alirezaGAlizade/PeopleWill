import type { PropsWithChildren } from 'react';
import PublicFooter from '@/components/public-footer';
import PublicHeader from '@/components/public-header';
import { cn } from '@/lib/utils';

export default function PublicLayout({
    children,
    contentClassName,
    sidebars = true,
}: PropsWithChildren<{
    contentClassName?: string;
    sidebars?: boolean;
}>): JSX.Element {
    return (
        <div className="bg-background text-foreground flex min-h-screen flex-col">
            <PublicHeader />

            <main className="flex min-h-0 flex-1 flex-col lg:flex-row">
                {sidebars ? (
                    <>
                        <aside
                            className="border-border bg-muted/20 hidden shrink-0 self-stretch border-e lg:block lg:w-1/5"
                            aria-hidden
                        >
                            <div className="h-full min-h-full" />
                        </aside>

                        <section
                            className={cn(
                                'min-w-0 flex-1 px-4 py-8 sm:px-6',
                                contentClassName,
                            )}
                        >
                            {children}
                        </section>

                        <aside
                            className="border-border bg-muted/20 hidden shrink-0 self-stretch border-s lg:block lg:w-1/5"
                            aria-hidden
                        >
                            <div className="h-full min-h-full" />
                        </aside>
                    </>
                ) : (
                    <div className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6">
                        <div className={cn(contentClassName)}>{children}</div>
                    </div>
                )}
            </main>

            <PublicFooter />
        </div>
    );
}
