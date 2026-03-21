import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { destroy, edit, index as questionsIndex } from '@/routes/questions';
import type { BreadcrumbItem } from '@/types';

type QuestionRow = {
    id: number;
    body: string;
    created_at: string;
    updated_at: string;
};

type PaginatedQuestions = {
    data: QuestionRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

function truncateBody(body: string, maxLength: number = 120): string {
    if (body.length <= maxLength) {
        return body;
    }

    return `${body.slice(0, maxLength).trim()}…`;
}

export default function QuestionsIndex({
    questions,
}: {
    questions: PaginatedQuestions;
}) {
    const { t, locale } = useTranslations();
    const [questionToDelete, setQuestionToDelete] = useState<QuestionRow | null>(
        null,
    );

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
        ],
        [t],
    );

    const confirmDelete = (): void => {
        if (questionToDelete === null) {
            return;
        }

        router.delete(destroy.url(questionToDelete.id), {
            preserveScroll: true,
            onFinish: () => setQuestionToDelete(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('questions.index_title')} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <Heading
                    variant="small"
                    title={t('questions.index_title')}
                    description={t('questions.index_description')}
                />

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table className="w-full min-w-[32rem] text-left text-sm">
                        <thead className="border-b border-sidebar-border/70 bg-muted/40 dark:border-sidebar-border">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    {t('questions.table_question')}
                                </th>
                                <th className="hidden px-4 py-3 font-medium sm:table-cell">
                                    {t('questions.table_submitted')}
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    {t('questions.table_actions')}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {questions.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={3}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        {t('questions.empty')}
                                    </td>
                                </tr>
                            ) : (
                                questions.data.map((question) => (
                                    <tr
                                        key={question.id}
                                        className="border-b border-sidebar-border/40 last:border-0 dark:border-sidebar-border/40"
                                    >
                                        <td className="max-w-md px-4 py-3 align-top">
                                            <span className="line-clamp-3 text-foreground">
                                                {truncateBody(question.body)}
                                            </span>
                                            <span className="mt-1 block text-xs text-muted-foreground sm:hidden">
                                                {new Date(
                                                    question.created_at,
                                                ).toLocaleDateString(locale)}
                                            </span>
                                        </td>
                                        <td className="hidden px-4 py-3 align-top text-muted-foreground sm:table-cell">
                                            {new Date(
                                                question.created_at,
                                            ).toLocaleDateString(locale)}
                                        </td>
                                        <td className="px-4 py-3 align-top">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit.url(
                                                            question.id,
                                                        )}
                                                    >
                                                        <Pencil className="mr-1 size-3.5" />
                                                        {t('questions.edit')}
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    type="button"
                                                    onClick={() =>
                                                        setQuestionToDelete(
                                                            question,
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="mr-1 size-3.5" />
                                                    {t('questions.delete')}
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
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
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={questions.prev_page_url}>
                                        {t('questions.previous')}
                                    </Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" disabled>
                                    {t('questions.previous')}
                                </Button>
                            )}
                            <span className="text-muted-foreground">
                                {t('questions.page_of', {
                                    current: questions.current_page,
                                    last: questions.last_page,
                                })}
                            </span>
                            {questions.next_page_url ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={questions.next_page_url}>
                                        {t('questions.next')}
                                    </Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" disabled>
                                    {t('questions.next')}
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            <Dialog
                open={questionToDelete !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setQuestionToDelete(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('questions.delete_title')}</DialogTitle>
                        <DialogDescription>
                            {t('questions.delete_description')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setQuestionToDelete(null)}
                        >
                            {t('questions.cancel')}
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={confirmDelete}
                        >
                            {t('questions.delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
