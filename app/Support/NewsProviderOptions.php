<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class NewsProviderOptions
{
    /**
     * Create a new instance of NewsProviderOptions.
     *
     * @param  Carbon  $fromDate  The start date for the query.
     * @param  Carbon|null  $toDate  The end date for the query.
     * @param  int  $limit  Maximum number of articles to fetch.
     */
    public function __construct(
        public Carbon $fromDate,
        public ?Carbon $toDate = null,
        public int $limit = 50,
        protected $keywords = []
    ) {
        $this->toDate = $toDate ?? now();
    }

    public function addKeyword(string $keyword): self
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function getFromDate(): Carbon
    {
        return $this->fromDate;
    }

    public function getToDate(): Carbon
    {
        return $this->toDate;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
