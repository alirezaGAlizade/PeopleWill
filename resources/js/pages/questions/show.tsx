import { Head, router } from '@inertiajs/react';
import { ArrowBigUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { toggle as toggleVote } from '@/actions/App/Http/Controllers/VoteController';

type ShowQuestion = {
    id: number;
    body: string;
    created_at: string;
    visits: number;
    upvotes_count: number;
    user: {
        id: number;
        name: string;
    };
};

export default function QuestionShow({
    question,
    userVote,
}: {
    question: ShowQuestion;
    userVote: 'up' | 'down' | null;
}): JSX.Element {
    const { t, locale } = useTranslations();
    const isUpvoted = userVote === 'up';

    const handleUpvote = (): void => {
        router.post(
            toggleVote.url({
                voteable_type: 'question',
                voteable_id: question.id,
            }),
            { type: 'up' },
            { preserveScroll: true },
        );
    };

    return (
        <PublicLayout>
            <Head title={t('questions.browse_title')} />

            <Card className="gap-4">
                <CardHeader className="space-y-2">
                    <CardTitle className="text-2xl font-semibold">
                        {t('questions.browse_title')}
                    </CardTitle>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                        <span>
                            {t('questions.asked_by', { name: question.user.name })}
                        </span>
                        <span>
                            {new Date(question.created_at).toLocaleDateString(
                                locale,
                            )}
                        </span>
                    </div>
                </CardHeader>

                <CardContent className="space-y-6">
                    <p className="text-base leading-7">{question.body}</p>

                    <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                        <span>
                            {t('questions.visits', { count: question.visits })}
                        </span>
                        <span>
                            {t('questions.upvotes', {
                                count: question.upvotes_count,
                            })}
                        </span>
                    </div>

                    <div>
                        <Button
                            type="button"
                            variant={isUpvoted ? 'default' : 'outline'}
                            onClick={handleUpvote}
                            className="gap-1.5"
                        >
                            <ArrowBigUp className="size-4" />
                            {isUpvoted
                                ? t('questions.upvoted')
                                : t('questions.upvote')}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </PublicLayout>
    );
}
