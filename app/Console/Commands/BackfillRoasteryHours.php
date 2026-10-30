<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackfillRoasteryHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-roastery-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roasteries = \App\Models\Roastery::all();
        $this->info("Found {$roasteries->count()} roasteries. Starting sync...");

        foreach ($roasteries as $roastery) {
            $roastery->save();
            $this->info(" synced: {$roastery->name}");
        }

        $this->info('All done!');
    }
}
