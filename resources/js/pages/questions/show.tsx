import { Form, Head, router } from '@inertiajs/react';
import {
    ArrowBigDown,
    ArrowBigUp,
    CalendarDays,
    Eye,
    MapPin,
    ShieldCheck,
    UserRound,
} from 'lucide-react';
import { store as storeOfficialResponse } from '@/actions/App/Http/Controllers/OfficialQuestionResponseController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
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

type QuestionResponseRow = {
    id: number;
    body: string;
    sequence: number;
    created_at: string | null;
    user: {
        id: number;
        name: string;
    };
    upvotes_count: number;
    downvotes_count: number;
    user_vote: 'up' | 'down' | null;
};

export default function QuestionShow({
    question,
    userVote,
    questionResponses = [],
    canRespondAsOfficial = false,
}: {
    question: ShowQuestion;
    userVote: 'up' | 'down' | null;
    questionResponses?: QuestionResponseRow[];
    canRespondAsOfficial?: boolean;
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

    const handleQuestionUpvote = (): void => {
        router.post(
            toggleVote.url({
                voteable_type: 'question',
                voteable_id: question.id,
            }),
            { type: 'up' },
            { preserveScroll: true },
        );
    };

    const handleResponseVote = (
        responseId: number,
        type: 'up' | 'down',
    ): void => {
        router.post(
            toggleVote.url({
                voteable_type: 'question_response',
                voteable_id: responseId,
            }),
            { type },
            { preserveScroll: true },
        );
    };

    return (
        <PublicLayout>
            <Head title={t('questions.show_title')} />

            <div className="space-y-8">
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
                            {question.effective_area === 'city' &&
                                question.city && (
                                    <Badge
                                        variant="outline"
                                        className="bg-background/70"
                                    >
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
                                {t('questions.asked_by', {
                                    name: question.user.name,
                                })}
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
                                {t('questions.visits', {
                                    count: question.visits,
                                })}
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
                                onClick={handleQuestionUpvote}
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

                {questionResponses.map((response) => {
                    const title =
                        response.sequence === 1
                            ? t('questions.official_response_primary')
                            : t('questions.official_response_follow_up');
                    const responseDate = response.created_at
                        ? new Date(response.created_at).toLocaleDateString(
                              locale,
                          )
                        : '';
                    const isRespUp = response.user_vote === 'up';
                    const isRespDown = response.user_vote === 'down';

                    return (
                        <Card
                            key={response.id}
                            className="border-border/80 shadow-sm"
                        >
                            <CardHeader className="space-y-2 pb-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle className="text-lg">{title}</CardTitle>
                                    <Badge variant="outline">{responseDate}</Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {t('questions.response_from', {
                                        name: response.user.name,
                                    })}
                                </p>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="whitespace-pre-wrap text-sm leading-relaxed">
                                    {response.body}
                                </p>
                                <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                    <span className="inline-flex items-center gap-1">
                                        <ArrowBigUp className="size-4" />
                                        {t('questions.response_upvotes', {
                                            count: response.upvotes_count,
                                        })}
                                    </span>
                                    <span className="inline-flex items-center gap-1">
                                        <ArrowBigDown className="size-4" />
                                        {t('questions.response_downvotes', {
                                            count: response.downvotes_count,
                                        })}
                                    </span>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            isRespUp ? 'default' : 'outline'
                                        }
                                        className="gap-1"
                                        onClick={() => {
                                            handleResponseVote(response.id, 'up');
                                        }}
                                    >
                                        <ArrowBigUp className="size-4" />
                                        {t('questions.accept_response')}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={
                                            isRespDown ? 'destructive' : 'outline'
                                        }
                                        className={cn(
                                            'gap-1',
                                            isRespDown && 'text-destructive-foreground',
                                        )}
                                        onClick={() => {
                                            handleResponseVote(
                                                response.id,
                                                'down',
                                            );
                                        }}
                                    >
                                        <ArrowBigDown className="size-4" />
                                        {t('questions.reject_response')}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}

                {canRespondAsOfficial && (
                    <Card className="border-dashed border-primary/40">
                        <CardHeader>
                            <CardTitle className="text-lg">
                                {t('questions.submit_official_response')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                {...storeOfficialResponse.form({
                                    question: question.id,
                                })}
                                className="space-y-4"
                                resetOnSuccess
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <label
                                                htmlFor="official-response-body"
                                                className="text-sm font-medium"
                                            >
                                                {t('questions.response_body_label')}
                                            </label>
                                            <textarea
                                                id="official-response-body"
                                                name="body"
                                                required
                                                rows={6}
                                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex min-h-24 w-full rounded-md border px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                            />
                                            {errors.body && (
                                                <p className="text-sm text-destructive">
                                                    {errors.body}
                                                </p>
                                            )}
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {t('questions.submit_official_response')}
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </PublicLayout>
    );
}
