<?php

namespace App\Console\Commands;

use App\Services\QuestionLifecycleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('questions:evaluate-remediation-windows')]
#[Description('Apply second-response deadlines and remediation review outcomes.')]
class EvaluateQuestionRemediationWindowsCommand extends Command
{
    public function handle(QuestionLifecycleService $lifecycle): int
    {
        $n = $lifecycle->processExpiredRemediationWindows();

        $this->info("Processed {$n} question(s).");

        return self::SUCCESS;
    }
}
