import { Link, usePage } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard, home, login } from '@/routes';
import { browse } from '@/routes/questions';
import type { User } from '@/types';

type FooterEntry =
    | { kind: 'link'; translationKey: string; href: string }
    | { kind: 'static'; translationKey: string };

function buildFooterEntries(user: User | null): FooterEntry[] {
    const entries: FooterEntry[] = [
        { kind: 'link', translationKey: 'nav.home', href: home.url() },
        { kind: 'link', translationKey: 'nav.questions', href: browse.url() },
    ];

    if (user) {
        entries.push({
            kind: 'link',
            translationKey: 'nav.dashboard',
            href: dashboard.url(),
        });
    } else {
        entries.push({
            kind: 'link',
            translationKey: 'nav.login',
            href: login.url(),
        });
    }

    entries.push(
        { kind: 'static', translationKey: 'nav.news' },
        { kind: 'static', translationKey: 'nav.about_us' },
    );

    return entries;
}

export default function PublicFooter(): JSX.Element {
    const { t } = useTranslations();
    const year = new Date().getFullYear();
    const { auth } = usePage<{ auth: { user: User | null } }>().props;
    const entries = buildFooterEntries(auth.user);

    return (
        <footer className="border-border bg-neutral-100 dark:bg-neutral-900 border-t">
            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
                <nav
                    className="flex flex-wrap items-center justify-center gap-x-6 gap-y-2"
                    aria-label="Footer"
                >
                    {entries.map((entry) => (
                        <span key={`${entry.kind}-${entry.translationKey}`}>
                            {entry.kind === 'link' ? (
                                <Link
                                    href={entry.href}
                                    className="text-foreground/90 hover:text-foreground text-sm font-medium transition-colors"
                                >
                                    {t(entry.translationKey)}
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    className="text-foreground/90 hover:text-foreground text-sm font-medium transition-colors"
                                >
                                    {t(entry.translationKey)}
                                </button>
                            )}
                        </span>
                    ))}
                </nav>
                <p className="text-muted-foreground mt-6 text-center text-xs">
                    {t('footer.copyright', { year })}
                </p>
            </div>
        </footer>
    );
}
