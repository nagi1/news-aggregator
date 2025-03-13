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
        public int $limit = 50,
        protected $keywords = [],
        protected $categories = [],
        protected $sources = [],
        protected $authors = [],
        public ?Carbon $toDate = null,
        public ?string $query = null,
    ) {
        $this->toDate = $toDate ?? now();
    }

    public function addKeyword(string $keyword): self
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    public function addCategory(string $category): self
    {
        $this->categories[] = $category;

        return $this;
    }

    public function addSource(string $source): self
    {
        $this->sources[] = $source;

        return $this;
    }

    public function addAuthor(string $author): self
    {
        $this->authors[] = $author;

        return $this;
    }

    public function getAuthors(): array
    {
        return $this->authors ?? [];
    }

    public function getCategories(): array
    {
        return $this->categories ?? [];
    }

    public function getSources(): array
    {
        return $this->sources ?? [];
    }

    public function getKeywords(): array
    {
        return $this->keywords ?? [];
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

    public function getQuery(): ?string
    {
        return $this->query;
    }
}
