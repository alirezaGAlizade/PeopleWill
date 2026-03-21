import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { show } from '@/routes/questions';

type BrowseQuestion = {
    id: number;
    body: string;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
};

type PaginatedQuestions = {
    data: BrowseQuestion[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export default function QuestionsBrowse({
    questions,
}: {
    questions: PaginatedQuestions;
}): JSX.Element {
    const { t, locale } = useTranslations();

    return (
        <PublicLayout>
            <Head title={t('questions.browse_title')} />

            <div className="space-y-4">
                <h1 className="text-2xl font-semibold">
                    {t('questions.browse_title')}
                </h1>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {questions.data.map((question) => (
                        <Link
                            key={question.id}
                            href={show.url(question.id)}
                            className="block h-full"
                        >
                            <Card className="hover:bg-muted/30 flex aspect-square h-full flex-col rounded-xl transition-colors">
                                <CardHeader>
                                    <CardTitle className="line-clamp-1 text-base">
                                        {question.body}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                    <span className="line-clamp-1">
                                        {t('questions.asked_by', { name: question.user.name })}
                                    </span>
                                    <span>{new Date(question.created_at).toLocaleDateString(locale)}</span>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {questions.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-4 text-sm">
                        <p className="text-muted-foreground">
                            {t('questions.showing', {
                                count: questions.data.length,
                                total: questions.total,
                            })}
                        </p>
                        <div className="flex items-center gap-2">
                            {questions.prev_page_url ? (
                                <Link
                                    href={questions.prev_page_url}
                                    className="border-border bg-background hover:bg-muted rounded-md border px-3 py-1.5"
                                >
                                    {t('questions.previous')}
                                </Link>
                            ) : (
                                <span className="border-border text-muted-foreground rounded-md border px-3 py-1.5">
                                    {t('questions.previous')}
                                </span>
                            )}

                            <span className="text-muted-foreground">
                                {t('questions.page_of', {
                                    current: questions.current_page,
                                    last: questions.last_page,
                                })}
                            </span>

                            {questions.next_page_url ? (
                                <Link
                                    href={questions.next_page_url}
                                    className="border-border bg-background hover:bg-muted rounded-md border px-3 py-1.5"
                                >
                                    {t('questions.next')}
                                </Link>
                            ) : (
                                <span className="border-border text-muted-foreground rounded-md border px-3 py-1.5">
                                    {t('questions.next')}
                                </span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
