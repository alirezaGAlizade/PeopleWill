import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import Select, {
    type ClassNamesConfig,
    type GroupBase,
    type StylesConfig,
} from 'react-select';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { cn } from '@/lib/utils';
import { cities as geoProvinceCities } from '@/routes/geo/provinces';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit(),
    },
];

type GeoRow = {
    id: number;
    name: string;
    name_en: string;
};

type ProvinceRow = GeoRow & { country: number };

type CityRow = GeoRow;

type SelectOption = { value: number; label: string };

function readXsrfToken(): string {
    const row = document.cookie.split('; ').find((r) => r.startsWith('XSRF-TOKEN='));

    if (!row) {
        return '';
    }

    return decodeURIComponent(row.split('=')[1] ?? '');
}

export default function Profile({
    mustVerifyEmail,
    status,
    countries,
    provinces,
    initialCities,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    countries: GeoRow[];
    provinces: ProvinceRow[];
    initialCities: CityRow[];
}) {
    const { auth, locale } = usePage().props as {
        auth: { user: Record<string, unknown> };
        locale: string;
    };
    const isEn = locale === 'en';

    const uCountry = auth.user.country_id as number | null | undefined;
    const uProvince = auth.user.province_id as number | null | undefined;
    const uCity = auth.user.city_id as number | null | undefined;

    const [countryId, setCountryId] = useState<number | null>(
        uCountry ?? countries[0]?.id ?? null,
    );
    const [provinceId, setProvinceId] = useState<number | null>(uProvince ?? null);
    const [cityId, setCityId] = useState<number | null>(uCity ?? null);

    const [cityOptions, setCityOptions] = useState<SelectOption[]>(() =>
        initialCities.map((c) => ({
            value: c.id,
            label: isEn ? c.name_en : c.name,
        })),
    );
    const [citiesLoading, setCitiesLoading] = useState(false);

    const provinceLabel = useCallback(
        (p: ProvinceRow) => (isEn ? p.name_en : p.name),
        [isEn],
    );

    const filteredProvinces = useMemo(
        () =>
            countryId === null
                ? []
                : provinces.filter((p) => p.country === countryId),
        [provinces, countryId],
    );

    const provinceOptions: SelectOption[] = useMemo(
        () =>
            filteredProvinces.map((p) => ({
                value: p.id,
                label: provinceLabel(p),
            })),
        [filteredProvinces, provinceLabel],
    );

    const selectedProvinceOption =
        provinceOptions.find((o) => o.value === provinceId) ?? null;

    const selectedCityOption =
        cityOptions.find((o) => o.value === cityId) ?? null;

    const loadCities = useCallback(
        async (nextProvinceId: number) => {
            setCitiesLoading(true);
            try {
                const response = await fetch(
                    geoProvinceCities.url(String(nextProvinceId)),
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
        if (provinceId === null) {
            setCityOptions([]);
            setCityId(null);

            return;
        }

        void loadCities(provinceId);
    }, [provinceId, loadCities]);

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
        indicatorSeparator: () => 'bg-border',
        dropdownIndicator: () => 'text-muted-foreground',
    };

    const selectStyles: StylesConfig<SelectOption, false, GroupBase<SelectOption>> =
        {
            control: (base) => ({ ...base, backgroundColor: 'transparent' }),
        };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name, email address, and location"
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="country_id"
                                    value={countryId ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="province_id"
                                    value={provinceId ?? ''}
                                />
                                <input
                                    type="hidden"
                                    name="city_id"
                                    value={cityId ?? ''}
                                />
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name as string}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder="Full name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email as string}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Country</Label>
                                    <Select<SelectOption, false>
                                        classNames={selectClassNames}
                                        styles={selectStyles}
                                        options={countries.map((c) => ({
                                            value: c.id,
                                            label: isEn ? c.name_en : c.name,
                                        }))}
                                        value={
                                            countries.find((c) => c.id === countryId)
                                                ? {
                                                      value: countryId as number,
                                                      label: isEn
                                                          ? countries.find(
                                                                (c) =>
                                                                    c.id ===
                                                                    countryId,
                                                            )?.name_en ?? ''
                                                          : countries.find(
                                                                (c) =>
                                                                    c.id ===
                                                                    countryId,
                                                            )?.name ?? '',
                                                  }
                                                : null
                                        }
                                        onChange={(opt) => {
                                            setCountryId(opt?.value ?? null);
                                            setProvinceId(null);
                                            setCityId(null);
                                        }}
                                        isSearchable
                                    />
                                    <InputError message={errors.country_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>Province</Label>
                                    <Select<SelectOption, false>
                                        classNames={selectClassNames}
                                        styles={selectStyles}
                                        options={provinceOptions}
                                        value={selectedProvinceOption}
                                        onChange={(opt) => {
                                            setProvinceId(opt?.value ?? null);
                                            setCityId(null);
                                        }}
                                        isDisabled={countryId === null}
                                        placeholder="Select province"
                                        isSearchable
                                    />
                                    <InputError message={errors.province_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label>City</Label>
                                    <Select<SelectOption, false>
                                        classNames={selectClassNames}
                                        styles={selectStyles}
                                        options={cityOptions}
                                        value={selectedCityOption}
                                        onChange={(opt) => {
                                            setCityId(opt?.value ?? null);
                                        }}
                                        isDisabled={
                                            provinceId === null || citiesLoading
                                        }
                                        placeholder={
                                            citiesLoading
                                                ? 'Loading…'
                                                : 'Select city'
                                        }
                                        isSearchable
                                    />
                                    <InputError message={errors.city_id} />
                                </div>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to resend the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={
                                            processing ||
                                            countryId === null ||
                                            provinceId === null ||
                                            cityId === null
                                        }
                                        data-test="update-profile-button"
                                    >
                                        Save
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            Saved
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
