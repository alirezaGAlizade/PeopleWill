---
name: multi-lang-translations
description: Multi-language (i18n) translation system for the NPAP platform. Activate when adding, editing, or debugging translation keys; working with lang/en/app.php or lang/fa/app.php; using the useTranslations hook or t() function in React; implementing locale switching; modifying SetLocale middleware; or adding new languages.
---

# Multi-Language Translation System

## How It Works (end-to-end)

```
Session (locale) → SetLocale middleware → app()->setLocale()
    → HandleInertiaRequests shares { locale, translations }
    → React: useTranslations() → t('key.path') renders text
    → switchLocale(next) POSTs to /locale → session updated → page reloads
```

## Supported Locales

| Locale | Language | Direction | Default |
|--------|----------|-----------|---------|
| `fa` | Farsi | RTL | Yes |
| `en` | English | LTR | No |

Defined in `SetLocale::SUPPORTED` and `SetLocale::DEFAULT_LOCALE`.

## Key Files

| File | Purpose |
|------|---------|
| `lang/en/app.php` | English translations (flat PHP array) |
| `lang/fa/app.php` | Farsi translations (same structure) |
| `app/Http/Middleware/SetLocale.php` | Reads `locale` from session, sets `app()->setLocale()` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shares `locale` and `translations` (via `Lang::get('app')`) to all Inertia pages |
| `resources/js/hooks/use-translations.ts` | React hook: `t()`, `switchLocale()`, `locale`, `isRtl` |
| `app/Http/Controllers/LocaleController.php` | `POST /locale` — validates and stores locale in session |
| `routes/web.php` | `Route::post('locale', ...)` named `locale.update` |
| `resources/js/routes/locale/index.ts` | Wayfinder-generated route for `locale.update` |

## Translation File Structure

Both `lang/en/app.php` and `lang/fa/app.php` share identical key structure:

```php
return [
    'nav' => [
        'home' => '...',
        'login' => '...',
        'logout' => '...',
        'dashboard' => '...',
        'news' => '...',
        'about_us' => '...',
    ],
    'footer' => [
        'copyright' => '© :year ...',  // :year is a placeholder
    ],
    'welcome' => [
        'title' => '...',
        'search_placeholder' => '...',
        'ask' => '...',
        'login_to_ask' => '...',
    ],
    'common' => [
        'language' => '...',
        'open_menu' => '...',
        'close_menu' => '...',
    ],
    'dashboard' => [
        'title' => '...',           // dashboard page + breadcrumb
        'sidebar_platform' => '...', // NavMain SidebarGroupLabel ("Platform")
        'my_questions' => '...',     // sidebar link to questions index
    ],
    'questions' => [
        // Index + edit: headings, table, pagination (:count, :total, :current, :last),
        // delete dialog, effective-area fieldset, selects, save/reset/saved
        'index_title' => '...',
        // ... see lang/en/app.php for full key list
    ],
];
```

**Authenticated dashboard UI:** `resources/js/components/app-sidebar.tsx` builds main nav items with `t('dashboard.title')` and `t('dashboard.my_questions')` (stable optional `NavItem.id` for React keys when titles are translated). `resources/js/components/nav-main.tsx` uses `t('dashboard.sidebar_platform')` for the sidebar group label.

## Frontend Hook — `useTranslations()`

```tsx
import { useTranslations } from '@/hooks/use-translations';

const { t, locale, switchLocale, isRtl, translations } = useTranslations();
```

| Return | Type | Purpose |
|--------|------|---------|
| `t(key, params?)` | `(string, Record?) => string` | Resolve dot-path key; replace `:param` placeholders |
| `locale` | `'en' \| 'fa'` | Current locale |
| `switchLocale(next)` | `(Locale) => void` | POST to `/locale`, page reloads with new language |
| `isRtl` | `boolean` | `true` when `locale === 'fa'` — use for RTL layout |
| `translations` | `Record<string, unknown>` | Raw translations object (rarely needed directly) |

### Using `t()` with placeholders

```tsx
t('footer.copyright', { year: '2026' })
// English: "© 2026 All rights reserved."
// Farsi:   "© 2026 تمامی حقوق محفوظ است."
```

Placeholders use `:name` syntax in PHP strings (Laravel convention).

### Fallback behavior

If a key is missing, `t()` returns the key string itself (e.g. `'missing.key'`).

## Adding New Translation Keys

1. Add the key to **both** `lang/en/app.php` and `lang/fa/app.php` under the appropriate group.
2. Use the key in React via `t('group.key_name')`.
3. Both files **must** have the same key structure — missing keys silently fall back to the key path string.

## Adding a New Language

1. Create `lang/{code}/app.php` with the same key structure.
2. Add the locale code to `SetLocale::SUPPORTED` array.
3. Add a button in the `LanguageSwitcher` component (inline in `welcome.tsx`).
4. Update `Locale` type in `use-translations.ts`: `export type Locale = 'en' | 'fa' | '{code}';`.
5. If RTL, add `|| locale === '{code}'` to the `isRtl` check in the hook.
6. Update `resources/js/types/global.d.ts` if the `locale` type is referenced there.

## Language Switcher

Defined inline in `resources/js/pages/welcome.tsx` as the `LanguageSwitcher` function component. Renders FA/EN toggle buttons that call `switchLocale()`.

## Backend Locale Route

```php
// routes/web.php
Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');
```

`LocaleController::update()` validates against `SetLocale::SUPPORTED`, stores in session, redirects back.
