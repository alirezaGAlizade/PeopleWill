<?php

namespace App\Console\Commands;

use App\Services\QuestionLifecycleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('questions:evaluate-validation-windows')]
#[Description('Close response validation windows and apply satisfaction or remediation rules.')]
class EvaluateQuestionValidationWindowsCommand extends Command
{
    public function handle(QuestionLifecycleService $lifecycle): int
    {
        $n = $lifecycle->processExpiredValidationWindows();

        $this->info("Processed {$n} question(s).");

        return self::SUCCESS;
    }
}
