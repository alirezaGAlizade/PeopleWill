import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { show } from '@/routes/questions';
import type { BreadcrumbItem } from '@/types';

type OfficialQuestion = {
    id: number;
    body: string;
    status: string;
    official_role: { id: number; name: string } | null;
};

export default function DashboardPage({
    officialActionQuestions = [],
}: {
    officialActionQuestions?: OfficialQuestion[];
}): JSX.Element {
    const { t } = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: t('dashboard.title'),
                href: dashboard(),
            },
        ],
        [t],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard.title')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">
                        {t('dashboard.official_actions_title')}
                    </h2>
                    {officialActionQuestions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            {t('dashboard.official_actions_empty')}
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {officialActionQuestions.map((q) => (
                                <li
                                    key={q.id}
                                    className="flex flex-col gap-1 rounded-lg border border-border/80 bg-card/50 p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p className="line-clamp-2 text-sm font-medium">
                                            {q.body}
                                        </p>
                                        {q.official_role && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {q.official_role.name}
                                            </p>
                                        )}
                                    </div>
                                    <Link
                                        href={show.url(q.id)}
                                        className="mt-2 shrink-0 text-sm font-medium text-primary underline-offset-4 hover:underline sm:mt-0"
                                    >
                                        {t('dashboard.view_question')}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[40vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
