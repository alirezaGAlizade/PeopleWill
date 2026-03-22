<?php

namespace App\Console\Commands;

use App\Services\QuestionLifecycleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('questions:evaluate-response-deadlines')]
#[Description('Mark questions as not accepted when the official response deadline passes without a primary response.')]
class EvaluateQuestionResponseDeadlinesCommand extends Command
{
    public function handle(QuestionLifecycleService $lifecycle): int
    {
        $n = $lifecycle->processExpiredResponseDeadlines();

        $this->info("Processed {$n} question(s).");

        return self::SUCCESS;
    }
}
