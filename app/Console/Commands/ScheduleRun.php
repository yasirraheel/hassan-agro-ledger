<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ScheduleRun extends Command
{
    protected $signature = 'schedule:run';

    protected $description = 'Run the scheduled queue worker for high, medium, and low priority queues';

    public function handle(): int
    {
        $this->info('Running queue workers every minute for shared hosting...');

        $this->call('queue:work', [
            '--queue' => 'high,medium,low',
            '--tries' => 3,
            '--timeout' => 60,
        ]);

        return Command::SUCCESS;
    }
}