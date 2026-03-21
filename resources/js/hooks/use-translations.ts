import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo } from 'react';
import { update as localeUpdate } from '@/routes/locale';

export type Locale = 'en' | 'fa';

function getByPath(obj: unknown, path: string): unknown {
    if (!path) {
        return undefined;
    }

    const parts = path.split('.');
    let current: unknown = obj;

    for (const part of parts) {
        if (
            current !== null &&
            typeof current === 'object' &&
            part in (current as object)
        ) {
            current = (current as Record<string, unknown>)[part];
        } else {
            return undefined;
        }
    }

    return current;
}

function replacePlaceholders(
    template: string,
    params?: Record<string, string | number>,
): string {
    if (!params) {
        return template;
    }

    return template.replace(/:(\w+)/g, (_, key: string) => {
        const value = params[key];

        return value !== undefined ? String(value) : `:${key}`;
    });
}

export type UseTranslationsReturn = {
    readonly locale: Locale;
    readonly translations: Record<string, unknown>;
    readonly t: (key: string, params?: Record<string, string | number>) => string;
    readonly switchLocale: (next: Locale) => void;
    readonly isRtl: boolean;
};

export function useTranslations(): UseTranslationsReturn {
    const page = usePage<{
        locale: Locale;
        translations: Record<string, unknown>;
    }>();
    const { locale, translations } = page.props;

    const t = useCallback(
        (key: string, params?: Record<string, string | number>) => {
            const raw = getByPath(translations, key);

            if (typeof raw !== 'string') {
                return key;
            }

            return replacePlaceholders(raw, params);
        },
        [translations],
    );

    const switchLocale = useCallback((next: Locale) => {
        if (next === locale) {
            return;
        }

        router.post(
            localeUpdate.url(),
            { locale: next },
            {
                preserveScroll: true,
            },
        );
    }, [locale]);

    const isRtl = locale === 'fa';

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
    }, [locale, isRtl]);

    return useMemo(
        () => ({
            locale,
            translations,
            t,
            switchLocale,
            isRtl,
        }),
        [locale, translations, t, switchLocale, isRtl],
    );
}
