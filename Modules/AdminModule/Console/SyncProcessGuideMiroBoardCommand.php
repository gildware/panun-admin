<?php

namespace Modules\AdminModule\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SyncProcessGuideMiroBoardCommand extends Command
{
    protected $signature = 'process-guide:sync-miro-board
                            {source? : Path to layout_read JSON or raw DSL file}
                            {--raw : Source file is raw DSL text, not MCP JSON}
                            {--layout : Run auto-layout after sync (off by default)}';

    protected $description = 'Regenerate process-guide miro-board.json from Miro MCP layout_read DSL';

    public function handle(): int
    {
        $source = $this->argument('source') ?? base_path('scripts/miro-layout-read.json');
        $script = base_path('scripts/sync-miro-board-from-dsl.py');

        if (! is_file($script)) {
            $this->error("Missing converter script: {$script}");

            return self::FAILURE;
        }

        if (! is_file($source)) {
            $this->error("Missing layout_read source: {$source}");
            $this->line('Save Miro MCP layout_read output to scripts/miro-layout-read.json first.');

            return self::FAILURE;
        }

        $cmd = ['python3', $script];
        if ($this->option('raw')) {
            $cmd[] = '--raw';
        }
        $cmd[] = $source;

        $process = new Process($cmd, base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        $this->info(trim($process->getOutput()));

        if ($this->option('layout') && ! $this->runLayoutScript()) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runLayoutScript(): bool
    {
        $layoutScript = base_path('scripts/layout-process-guide-board.py');
        if (! is_file($layoutScript)) {
            $this->warn('Layout script missing — skipping auto-layout.');

            return true;
        }

        $layout = new Process(['python3', $layoutScript], base_path());
        $layout->run();

        if (! $layout->isSuccessful()) {
            $this->error(trim($layout->getErrorOutput() ?: $layout->getOutput()));

            return false;
        }

        $this->info(trim($layout->getOutput()));

        return true;
    }
}
