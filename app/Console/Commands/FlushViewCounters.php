<?php

namespace App\Console\Commands;

use App\Models\Information;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class FlushViewCounters extends Command
{
    protected $signature = 'app:flush-view-counters';

    protected $description = 'Flush cached view counters to the database';

    public function handle(): int
    {
        $flushed = 0;

        Information::query()->select('id')->chunk(100, function ($articles) use (&$flushed): void {
            foreach ($articles as $article) {
                $key = "info_views:{$article->id}";
                $views = (int) Cache::pull($key, 0);

                if ($views > 0) {
                    Information::where('id', $article->id)->increment('views', $views);
                    $flushed++;
                }
            }
        });

        $this->info("Flushed view counters for {$flushed} articles.");

        return self::SUCCESS;
    }
}
