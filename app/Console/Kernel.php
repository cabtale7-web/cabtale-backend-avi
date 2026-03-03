<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('trip-request:cancel')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
        /**
         * The Artisan commands provided by your application.
         *
         * @var array
         */
        protected $commands = [
            \App\Console\Commands\TestConnectionsCommand::class,
        ];
    
        protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
