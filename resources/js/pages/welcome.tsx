import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { login } from '@/routes';
import { store as storeQuestion } from '@/routes/questions';
import type { User } from '@/types';

export default function Welcome({
    canRegister: _canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const [showGuestHint, setShowGuestHint] = useState(false);
    const { t } = useTranslations();
    const { auth } = usePage<{ auth: { user: User | null } }>().props;
    const user = auth.user;

    const questionForm = useForm({ body: '' });

    const submitQuestion = (e?: FormEvent) => {
        e?.preventDefault();
        if (!user) {
            return;
        }

        questionForm.post(storeQuestion.url(), {
            preserveScroll: true,
            onSuccess: () => {
                questionForm.reset('body');
            },
        });
    };

    return (
        <>
            <Head title={t('welcome.title')} />
            <PublicLayout sidebars={false} contentClassName="relative min-h-[60vh]">
                <div
                    className="pointer-events-none absolute inset-0 bg-[length:min(90vw,42rem)] bg-center bg-no-repeat opacity-[0.12] dark:opacity-[0.15]"
                    style={{
                        backgroundImage: 'url(/assets/images/background.png)',
                    }}
                    aria-hidden
                />
                <div className="relative flex min-h-[60vh] items-center justify-center px-2 py-10">
                    <div className="w-full max-w-2xl">
                        <form onSubmit={submitQuestion} className="flex flex-col gap-2">
                            <div className="group border-border bg-muted/40 focus-within:border-ring focus-within:shadow-md relative flex w-full items-center rounded-full border shadow-sm transition-shadow dark:bg-neutral-800/60">
                                <input
                                    type="text"
                                    readOnly={!user}
                                    aria-readonly={!user}
                                    value={questionForm.data.body}
                                    onChange={(e) =>
                                        questionForm.setData(
                                            'body',
                                            e.target.value,
                                        )
                                    }
                                    onFocus={() => {
                                        if (!user) {
                                            setShowGuestHint(true);
                                        }
                                    }}
                                    className="text-foreground placeholder:text-muted-foreground w-full rounded-full border-0 bg-transparent py-3.5 ps-5 pe-28 text-base outline-none ring-0 disabled:cursor-not-allowed"
                                    placeholder={t('welcome.search_placeholder')}
                                    disabled={questionForm.processing && !!user}
                                />
                                <button
                                    type={user ? 'submit' : 'button'}
                                    onClick={() => {
                                        if (!user) {
                                            setShowGuestHint(true);
                                        }
                                    }}
                                    disabled={user && questionForm.processing}
                                    className="text-primary-foreground bg-primary hover:bg-primary/90 absolute end-1.5 top-1/2 inline-flex -translate-y-1/2 shrink-0 items-center rounded-full border border-border/50 px-4 py-2 text-sm font-medium shadow-sm transition-colors disabled:pointer-events-none disabled:opacity-50 dark:border-neutral-600"
                                >
                                    {t('welcome.ask')}
                                </button>
                            </div>
                            {questionForm.errors.body && (
                                <p className="text-destructive px-2 text-sm">
                                    {questionForm.errors.body}
                                </p>
                            )}
                        </form>
                        {showGuestHint && !user && (
                            <p className="text-muted-foreground mt-3 px-2 text-center text-sm">
                                {t('welcome.login_to_ask')}{' '}
                                <Link
                                    href={login.url()}
                                    className="text-primary font-medium underline-offset-4 hover:underline"
                                >
                                    {t('nav.login')}
                                </Link>
                            </p>
                        )}
                    </div>
                </div>
            </PublicLayout>
        </>
    );
}
