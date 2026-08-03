<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class LayoutProcessGuideBoardCommand extends Command
{
    protected $signature = 'process-guide:layout-board
                            {file? : Board JSON path (defaults to public miro-board.json)}';

    protected $description = 'Auto-layout process guide board with vertical straight connectors';

    public function handle(): int
    {
        $script = base_path('scripts/layout-process-guide-board.py');
        if (! is_file($script)) {
            $this->error("Missing layout script: {$script}");

            return self::FAILURE;
        }

        $file = $this->argument('file') ?? public_path('assets/admin-module/process-guide/miro-board.json');
        $process = new Process(['python3', $script, $file], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        $this->info(trim($process->getOutput()));

        return self::SUCCESS;
    }
}
