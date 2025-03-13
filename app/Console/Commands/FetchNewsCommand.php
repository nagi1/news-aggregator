<?php

namespace App\Console\Commands;

use App\Enums\NewsProviderEnum;
use App\Jobs\FetchArticlesJob;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:news {provider} {--limit=100}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Latest News from a provider';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $provider = NewsProviderEnum::from($this->argument('provider'));

        $this->info("Fetching news from {$provider->value}...");

        FetchArticlesJob::dispatch($provider->value, $this->option('limit'));

        $this->info("a job has been dispatched to fetch news from {$provider->value}");

        return Command::SUCCESS;
    }
}
