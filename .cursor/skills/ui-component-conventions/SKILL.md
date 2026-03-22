---
name: ui-component-conventions
description: UI conventions for NPAP React pages. Activate when implementing alerts, confirmations, warnings, or dropdown/select inputs. Use SweetAlert2 for all user-facing alerts/confirmations and ReactSelect for all select/dropdown controls.
---

# UI Component Conventions

## Scope

These rules standardize user interaction components across the React frontend.

## Alerts and Confirmations: SweetAlert2 Required

Use `sweetalert2` for all user-facing:

- confirmation dialogs,
- warning dialogs,
- informational alerts,
- destructive action confirmations.

Do not use:

- `window.alert()`
- `window.confirm()`

### Standard import

```tsx
import Swal from 'sweetalert2';
```

### Standard usage pattern

```tsx
const result = await Swal.fire({
    title: t('some.title_key'),
    text: t('some.text_key'),
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: t('some.confirm_key'),
    cancelButtonText: t('common.cancel_key'),
});

if (!result.isConfirmed) {
    return;
}
```

### Translation requirement

- Use translated labels via `t()` from `useTranslations()`.
- Add missing translation keys in both `lang/en/app.php` and `lang/fa/app.php`.

### Existing reference implementation

- `resources/js/pages/questions/edit.tsx` (completion confirmation)

## Select Inputs: ReactSelect Required

Use `react-select` for all select/dropdown inputs (especially searchable selects and dependent selects).

Do not use native `<select>` for new or updated form selects.

### Standard imports

```tsx
import Select, {
    type ClassNamesConfig,
    type GroupBase,
    type StylesConfig,
} from 'react-select';
```

### Option type convention

```tsx
type SelectOption = {
    value: number;
    label: string;
};
```

### Styling convention

- Use `unstyled` prop.
- Provide shared `classNames` and `styles` config.
- Align styling with existing shadcn/Tailwind theme tokens.

Recommended baseline:

- `classNames={selectClassNames}`
- `styles={selectStyles}`
- `unstyled`
- `isClearable` where applicable
- `isLoading` / `isDisabled` for async or dependent fields

### Existing reference implementation

- `resources/js/pages/questions/edit.tsx` (official role, province, city selects)

## Implementation Checklist

When adding UI interactions:

1. If it is an alert/confirm flow, use SweetAlert2.
2. If it is a select/dropdown field, use ReactSelect.
3. Localize all user-visible strings.
4. Reuse the existing style pattern from `questions/edit.tsx`.
5. Keep behavior accessible (`disabled`, placeholders, and clear validation errors).
