import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import { dashboard, home, login, logout } from '@/routes';
import { browse } from '@/routes/questions';
import type { User } from '@/types';

type NavEntry =
    | { kind: 'link'; translationKey: string; href: string }
    | { kind: 'static'; translationKey: string }
    | { kind: 'logout'; translationKey: string };

function buildNavEntries(
    user: User | null,
    includeLogout: boolean,
): NavEntry[] {
    const entries: NavEntry[] = [
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

    if (includeLogout && user) {
        entries.push({ kind: 'logout', translationKey: 'nav.logout' });
    }

    return entries;
}

function LanguageSwitcher(): JSX.Element {
    const { locale, switchLocale, t } = useTranslations();

    return (
        <div
            className="border-border bg-muted/50 inline-flex items-center gap-0.5 rounded-lg border p-0.5"
            role="group"
            aria-label={t('common.language')}
        >
            <button
                type="button"
                className={cn(
                    'rounded-md px-2.5 py-1 text-xs font-semibold transition-colors',
                    locale === 'fa'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )}
                onClick={() => switchLocale('fa')}
            >
                FA
            </button>
            <button
                type="button"
                className={cn(
                    'rounded-md px-2.5 py-1 text-xs font-semibold transition-colors',
                    locale === 'en'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                )}
                onClick={() => switchLocale('en')}
            >
                EN
            </button>
        </div>
    );
}

function NavButtons({
    className,
    onItemClick,
    user,
    includeLogout = true,
}: {
    className?: string;
    onItemClick?: () => void;
    user: User | null;
    includeLogout?: boolean;
}): JSX.Element {
    const { t } = useTranslations();
    const entries = buildNavEntries(user, includeLogout);

    return (
        <ul
            className={cn(
                'flex flex-col gap-1 md:flex-row md:items-center md:gap-6',
                className,
            )}
        >
            {entries.map((entry) => (
                <li key={`${entry.kind}-${entry.translationKey}`}>
                    {entry.kind === 'link' && (
                        <Link
                            href={entry.href}
                            className="text-foreground hover:text-foreground/80 block w-full rounded-md px-2 py-2 text-left text-sm font-medium transition-colors md:inline-block md:w-auto md:py-1.5 rtl:text-right"
                            onClick={onItemClick}
                        >
                            {t(entry.translationKey)}
                        </Link>
                    )}
                    {entry.kind === 'static' && (
                        <button
                            type="button"
                            className="text-foreground hover:text-foreground/80 w-full rounded-md px-2 py-2 text-left text-sm font-medium transition-colors md:w-auto md:py-1.5 rtl:text-right"
                            onClick={onItemClick}
                        >
                            {t(entry.translationKey)}
                        </button>
                    )}
                    {entry.kind === 'logout' && (
                        <Link
                            href={logout.url()}
                            method="post"
                            as="button"
                            className="text-foreground hover:text-foreground/80 block w-full rounded-md px-2 py-2 text-left text-sm font-medium transition-colors md:inline-block md:w-auto md:py-1.5 rtl:text-right"
                            onClick={onItemClick}
                        >
                            {t(entry.translationKey)}
                        </Link>
                    )}
                </li>
            ))}
        </ul>
    );
}

export default function PublicHeader(): JSX.Element {
    const [mobileOpen, setMobileOpen] = useState(false);
    const { t } = useTranslations();
    const { auth } = usePage<{ auth: { user: User | null } }>().props;
    const user = auth.user;

    return (
        <header className="border-border bg-background/80 sticky top-0 z-50 border-b backdrop-blur-md dark:bg-neutral-900/80">
            <div className="mx-auto flex max-w-7xl items-center justify-end gap-4 px-4 py-3 sm:px-6 md:grid md:grid-cols-[1fr_auto_1fr] md:items-center md:justify-center">
                <div className="hidden min-w-0 md:block" aria-hidden />

                <nav className="hidden justify-center md:flex" aria-label="Main">
                    <NavButtons className="flex-row" user={user} />
                </nav>

                <div className="flex min-w-0 items-center justify-end gap-2 md:justify-self-end">
                    <LanguageSwitcher />
                    <AppearanceToggleTab className="hidden sm:inline-flex" />
                    <button
                        type="button"
                        className="text-foreground hover:bg-accent inline-flex rounded-md p-2 md:hidden"
                        onClick={() => setMobileOpen((open) => !open)}
                        aria-expanded={mobileOpen}
                        aria-controls="mobile-nav"
                        aria-label={
                            mobileOpen
                                ? t('common.close_menu')
                                : t('common.open_menu')
                        }
                    >
                        {mobileOpen ? (
                            <X className="h-6 w-6" />
                        ) : (
                            <Menu className="h-6 w-6" />
                        )}
                    </button>
                </div>
            </div>

            <div
                id="mobile-nav"
                className={cn(
                    'border-border border-t md:hidden',
                    mobileOpen ? 'block' : 'hidden',
                )}
            >
                <div className="mx-auto max-w-7xl px-4 py-3 sm:px-6">
                    <NavButtons
                        user={user}
                        onItemClick={() => setMobileOpen(false)}
                    />
                    <div className="mt-3 sm:hidden">
                        <AppearanceToggleTab className="w-full justify-center" />
                    </div>
                </div>
            </div>
        </header>
    );
}
