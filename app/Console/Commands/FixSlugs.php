<?php

namespace App\Console\Commands;

use App\Models\Cafe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSlugs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:fix-slugs {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Generate slugs for cafes that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cafes = Cafe::whereNull('slug')->orWhere('slug', '')->get();

        if ($cafes->isEmpty()) {
            $this->info('All cafes already have slugs. Nothing to do.');

            return self::SUCCESS;
        }

        $this->info("Found {$cafes->count()} cafe(s) without slugs.");

        if (! $this->option('force') && ! $this->confirm('Do you want to generate slugs for them?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($cafes->count());
        $bar->start();

        foreach ($cafes as $cafe) {
            $slug = Cafe::generateUniqueSlug($cafe->name);
            DB::table('cafes')->where('id', $cafe->id)->update(['slug' => $slug]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done! Generated slugs for {$cafes->count()} cafe(s).");

        return self::SUCCESS;
    }
}
