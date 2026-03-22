import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';
import Select, {
    type ClassNamesConfig,
    type GroupBase,
    type StylesConfig,
} from 'react-select';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { cities as provinceCitiesRoute } from '@/routes/api/provinces';
import {
    edit as editQuestion,
    index as questionsIndex,
    update,
} from '@/routes/questions';
import type { BreadcrumbItem } from '@/types';

type EffectiveAreaValue = 'public' | 'province' | 'city';

type ProvinceRow = {
    id: number;
    name: string;
    name_en: string;
};

type CityRow = {
    id: number;
    name: string;
    name_en: string;
};

type OfficialRoleRow = {
    id: number;
    name: string;
    slug: string;
};

type QuestionPayload = {
    id: number;
    body: string;
    status: string;
    official_role_id: number | null;
    effective_area: EffectiveAreaValue | null;
    province_id: number | null;
    city_id: number | null;
    province?: ProvinceRow | null;
    city?: CityRow | null;
    created_at: string;
    updated_at: string;
};

type SelectOption = { value: number; label: string };

function readXsrfToken(): string {
    const row = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='));

    if (!row) {
        return '';
    }

    return decodeURIComponent(row.split('=')[1] ?? '');
}

export default function EditQuestion({
    question,
    provinces,
    officialRoles,
}: {
    question: QuestionPayload;
    provinces: ProvinceRow[];
    officialRoles: OfficialRoleRow[];
}) {
    const { locale, t } = useTranslations();
    const isEn = locale === 'en';

    const initialEffectiveArea: EffectiveAreaValue =
        question.effective_area ?? 'public';

    const isDraftIncomplete = question.status === 'incomplete';

    const form = useForm({
        body: question.body,
        official_role_id: question.official_role_id ?? null,
        effective_area: initialEffectiveArea,
        province_id: question.province_id ?? null,
        city_id: question.city_id ?? null,
    });

    const [cityOptions, setCityOptions] = useState<SelectOption[]>(() => {
        if (question.city) {
            return [
                {
                    value: question.city.id,
                    label: isEn ? question.city.name_en : question.city.name,
                },
            ];
        }

        return [];
    });

    const [citiesLoading, setCitiesLoading] = useState(false);

    const provinceLabel = useCallback(
        (p: ProvinceRow) => (isEn ? p.name_en : p.name),
        [isEn],
    );

    const provinceOptions: SelectOption[] = useMemo(
        () =>
            provinces.map((p) => ({
                value: p.id,
                label: provinceLabel(p),
            })),
        [provinces, provinceLabel],
    );

    const selectedProvinceOption =
        provinceOptions.find((o) => o.value === form.data.province_id) ?? null;

    const selectedCityOption =
        cityOptions.find((o) => o.value === form.data.city_id) ?? null;

    const officialRoleOptions: SelectOption[] = useMemo(
        () =>
            officialRoles.map((role) => ({
                value: role.id,
                label: role.name,
            })),
        [officialRoles],
    );

    const selectedOfficialRoleOption =
        officialRoleOptions.find((o) => o.value === form.data.official_role_id) ??
        null;

    const loadCities = useCallback(
        async (provinceId: number) => {
            setCitiesLoading(true);
            try {
                const response = await fetch(
                    provinceCitiesRoute.url(String(provinceId)),
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-XSRF-TOKEN': readXsrfToken(),
                        },
                        credentials: 'same-origin',
                    },
                );

                if (!response.ok) {
                    setCityOptions([]);

                    return;
                }

                const data = (await response.json()) as CityRow[];
                setCityOptions(
                    data.map((c) => ({
                        value: c.id,
                        label: isEn ? c.name_en : c.name,
                    })),
                );
            } finally {
                setCitiesLoading(false);
            }
        },
        [isEn],
    );

    useEffect(() => {
        if (form.data.effective_area !== 'city') {
            return;
        }

        if (form.data.province_id === null) {
            setCityOptions([]);

            return;
        }

        void loadCities(form.data.province_id);
    }, [form.data.effective_area, form.data.province_id, loadCities]);

    const selectClassNames: ClassNamesConfig<
        SelectOption,
        false,
        GroupBase<SelectOption>
    > = {
        control: ({ isFocused }) =>
            cn(
                'min-h-9 w-full cursor-pointer rounded-md border border-input! bg-transparent! px-1 shadow-xs',
                isFocused &&
                    'border-ring! ring-[3px] ring-ring/50 dark:ring-ring/50',
            ),
        valueContainer: () => 'px-2 py-0.5',
        placeholder: () => 'text-muted-foreground',
        input: () => 'text-foreground',
        singleValue: () => 'text-foreground',
        menu: () =>
            'mt-1 rounded-md border border-border bg-popover text-popover-foreground shadow-md',
        menuList: () => 'py-1',
        option: ({ isFocused, isSelected }) =>
            cn(
                'cursor-pointer px-3 py-2 text-sm',
                isFocused && 'bg-accent text-accent-foreground',
                isSelected && 'bg-primary/15',
            ),
        indicatorsContainer: () => 'text-muted-foreground',
    };

    const selectStyles: StylesConfig<
        SelectOption,
        false,
        GroupBase<SelectOption>
    > = {
        control: (base) => ({ ...base, boxShadow: 'none' }),
    };

    const breadcrumbs: BreadcrumbItem[] = useMemo(
        () => [
            {
                title: t('dashboard.title'),
                href: dashboard(),
            },
            {
                title: t('questions.index_title'),
                href: questionsIndex(),
            },
            {
                title: t('questions.edit_title'),
                href: editQuestion.url(question.id),
            },
        ],
        [t, question.id],
    );

    const setEffectiveArea = (value: EffectiveAreaValue): void => {
        if (value === 'public') {
            form.setData({
                ...form.data,
                effective_area: value,
                province_id: null,
                city_id: null,
            });
            setCityOptions([]);

            return;
        }

        if (value === 'province') {
            form.setData({
                ...form.data,
                effective_area: value,
                city_id: null,
            });
            setCityOptions([]);

            return;
        }

        form.setData('effective_area', value);
    };

    const submit = async (e: React.FormEvent): Promise<void> => {
        e.preventDefault();

        if (isDraftIncomplete) {
            const result = await Swal.fire({
                title: t('questions.confirm_submit_title'),
                text: t('questions.confirm_submit_text'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: t('questions.confirm_submit_button'),
                cancelButtonText: t('questions.cancel'),
            });

            if (!result.isConfirmed) {
                return;
            }
        }

        form.put(update.url(question.id), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('questions.edit_title')} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    variant="small"
                    title={t('questions.edit_title')}
                    description={t('questions.edit_description')}
                />

                <form onSubmit={submit} className="max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="body">
                            {t('questions.label_question')}
                        </Label>
                        <textarea
                            id="body"
                            name="body"
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                            readOnly={!isDraftIncomplete}
                            required={isDraftIncomplete}
                            rows={8}
                            className={cn(
                                'border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                            )}
                            aria-invalid={Boolean(form.errors.body)}
                        />
                        <InputError message={form.errors.body} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="official_role_id">
                            {t('questions.label_role')}
                        </Label>
                        <Select<SelectOption, false>
                            inputId="official_role_id"
                            instanceId="question-official-role"
                            options={officialRoleOptions}
                            value={selectedOfficialRoleOption}
                            onChange={(opt) =>
                                form.setData(
                                    'official_role_id',
                                    opt?.value ?? null,
                                )
                            }
                            isClearable
                            placeholder={t('questions.select_role')}
                            classNames={selectClassNames}
                            styles={selectStyles}
                            unstyled
                        />
                        <InputError message={form.errors.official_role_id} />
                    </div>

                    <fieldset className="grid gap-3">
                        <legend className="text-sm font-medium">
                            {t('questions.effective_area')}
                        </legend>
                        <p className="text-sm text-muted-foreground">
                            {t('questions.effective_area_hint')}
                        </p>
                        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            <label className="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    type="radio"
                                    name="effective_area"
                                    value="public"
                                    checked={form.data.effective_area === 'public'}
                                    onChange={() => setEffectiveArea('public')}
                                    className="text-primary"
                                />
                                {t('questions.area_public')}
                            </label>
                            <label className="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    type="radio"
                                    name="effective_area"
                                    value="province"
                                    checked={
                                        form.data.effective_area === 'province'
                                    }
                                    onChange={() => setEffectiveArea('province')}
                                    className="text-primary"
                                />
                                {t('questions.area_province')}
                            </label>
                            <label className="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    type="radio"
                                    name="effective_area"
                                    value="city"
                                    checked={form.data.effective_area === 'city'}
                                    onChange={() => setEffectiveArea('city')}
                                    className="text-primary"
                                />
                                {t('questions.area_city')}
                            </label>
                        </div>
                        <InputError message={form.errors.effective_area} />
                    </fieldset>

                    {(form.data.effective_area === 'province' ||
                        form.data.effective_area === 'city') && (
                        <div className="grid gap-2">
                            <Label htmlFor="province_id">
                                {t('questions.label_province')}
                            </Label>
                            <Select<SelectOption, false>
                                inputId="province_id"
                                instanceId="question-province"
                                options={provinceOptions}
                                value={selectedProvinceOption}
                                onChange={(opt) => {
                                    form.setData({
                                        ...form.data,
                                        province_id: opt?.value ?? null,
                                        city_id: null,
                                    });
                                    if (form.data.effective_area === 'city') {
                                        setCityOptions([]);
                                    }
                                }}
                                isClearable
                                placeholder={t('questions.select_province')}
                                classNames={selectClassNames}
                                styles={selectStyles}
                                unstyled
                            />
                            <InputError message={form.errors.province_id} />
                        </div>
                    )}

                    {form.data.effective_area === 'city' && (
                        <div className="grid gap-2">
                            <Label htmlFor="city_id">
                                {t('questions.label_city')}
                            </Label>
                            <Select<SelectOption, false>
                                inputId="city_id"
                                instanceId="question-city"
                                options={cityOptions}
                                value={selectedCityOption}
                                onChange={(opt) =>
                                    form.setData(
                                        'city_id',
                                        opt?.value ?? null,
                                    )
                                }
                                isLoading={citiesLoading}
                                isDisabled={
                                    form.data.province_id === null ||
                                    citiesLoading
                                }
                                isClearable
                                placeholder={
                                    form.data.province_id === null
                                        ? t('questions.select_province_first')
                                        : t('questions.select_city')
                                }
                                classNames={selectClassNames}
                                styles={selectStyles}
                                unstyled
                            />
                            <InputError message={form.errors.city_id} />
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={form.processing}>
                            {t('questions.save_changes')}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => {
                                form.reset();
                                setCityOptions(
                                    question.city
                                        ? [
                                              {
                                                  value: question.city.id,
                                                  label: isEn
                                                      ? question.city.name_en
                                                      : question.city.name,
                                              },
                                          ]
                                        : [],
                                );
                            }}
                        >
                            {t('questions.reset')}
                        </Button>

                        <Transition
                            show={form.recentlySuccessful}
                            enter="transition ease-in-out"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-neutral-600 dark:text-neutral-400">
                                {t('questions.saved')}
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
