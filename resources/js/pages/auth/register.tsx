import { Form, Head } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import Select, {
    type ClassNamesConfig,
    type GroupBase,
    type StylesConfig,
} from 'react-select';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { cities as geoProvinceCities } from '@/routes/geo/provinces';
import { login } from '@/routes';
import { store } from '@/routes/register';

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

export default function Register({
    countries,
    provinces,
}: {
    countries: GeoRow[];
    provinces: ProvinceRow[];
}) {
    const { locale } = useTranslations();
    const isEn = locale === 'en';

    const [countryId, setCountryId] = useState<number | null>(
        countries[0]?.id ?? null,
    );
    const [provinceId, setProvinceId] = useState<number | null>(null);
    const [cityId, setCityId] = useState<number | null>(null);
    const [cityOptions, setCityOptions] = useState<SelectOption[]>([]);
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
        <AuthLayout
            title="Create an account"
            description="Enter your details below to create your account"
        >
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
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
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
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
                                    tabIndex={3}
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
                                    tabIndex={4}
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
                                    tabIndex={5}
                                />
                                <InputError message={errors.city_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={6}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={7}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={8}
                                data-test="register-user-button"
                                disabled={
                                    countryId === null ||
                                    provinceId === null ||
                                    cityId === null
                                }
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} tabIndex={9}>
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
