import { Head, router } from '@inertiajs/react';
import {
    ArrowBigUp,
    CalendarDays,
    Eye,
    MapPin,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { toggle as toggleVote } from '@/actions/App/Http/Controllers/VoteController';

type ShowQuestion = {
    id: number;
    body: string;
    created_at: string;
    visits: number;
    upvotes_count: number;
    status: string;
    effective_area: 'public' | 'province' | 'city' | null;
    official_role: {
        id: number;
        name: string;
    } | null;
    province: {
        id: number;
        name: string;
        name_en: string;
    } | null;
    city: {
        id: number;
        name: string;
        name_en: string;
    } | null;
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
    const formattedDate = new Date(question.created_at).toLocaleDateString(locale);

    const localeName = (item: { name: string; name_en: string }): string =>
        locale === 'en' ? item.name_en : item.name;

    const areaLabel = (() => {
        if (question.effective_area === 'province') {
            return t('questions.area_province');
        }

        if (question.effective_area === 'city') {
            return t('questions.area_city');
        }

        return t('questions.area_public');
    })();

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
            <Head title={t('questions.show_title')} />

            <Card className="relative overflow-hidden border-border/80 bg-linear-to-b from-background to-muted/30 shadow-sm">
                <div
                    className="pointer-events-none absolute inset-x-0 top-0 h-28 bg-linear-to-r from-primary/8 via-transparent to-primary/8"
                    aria-hidden
                />

                <CardHeader className="relative z-10 space-y-5 pb-3">
                    <div className="flex flex-wrap items-center gap-2">
                        {question.official_role && (
                            <Badge variant="secondary" className="gap-1.5">
                                <ShieldCheck className="size-3.5" />
                                {t('questions.directed_to')}:{' '}
                                {question.official_role.name}
                            </Badge>
                        )}
                        <Badge variant="outline" className="gap-1.5">
                            <MapPin className="size-3.5" />
                            {t('questions.scope')}: {areaLabel}
                        </Badge>
                        {(question.effective_area === 'province' ||
                            question.effective_area === 'city') &&
                            question.province && (
                                <Badge
                                    variant="outline"
                                    className="bg-background/70"
                                >
                                    {t('questions.label_province')}:{' '}
                                    {localeName(question.province)}
                                </Badge>
                            )}
                        {question.effective_area === 'city' && question.city && (
                            <Badge variant="outline" className="bg-background/70">
                                {t('questions.label_city')}:{' '}
                                {localeName(question.city)}
                            </Badge>
                        )}
                    </div>

                    <CardTitle className="text-balance text-2xl leading-9 font-semibold md:text-3xl">
                        {question.body}
                    </CardTitle>

                    <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted-foreground">
                        <span className="inline-flex items-center gap-1.5">
                            <UserRound className="size-4" />
                            {t('questions.asked_by', { name: question.user.name })}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <CalendarDays className="size-4" />
                            {t('questions.submitted_on', {
                                date: formattedDate,
                            })}
                        </span>
                    </div>
                </CardHeader>

                <CardContent className="relative z-10 space-y-6 pt-1">
                    <div className="grid gap-3 rounded-xl border bg-background/70 p-4 text-sm text-muted-foreground sm:grid-cols-2">
                        <span className="inline-flex items-center gap-2">
                            <Eye className="size-4" />
                            {t('questions.visits', { count: question.visits })}
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <ArrowBigUp className="size-4" />
                            {t('questions.upvotes', {
                                count: question.upvotes_count,
                            })}
                        </span>
                    </div>

                    <Separator />

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
