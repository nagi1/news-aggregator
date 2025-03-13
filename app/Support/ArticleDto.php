<?php

namespace App\Support;

use App\Enums\NewsProviderEnum;
use Illuminate\Support\Carbon;

class ArticleDto
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $description,
        public string $url,
        public Carbon $publishedAt,
        public NewsProviderEnum $apiProvider,
        public ?string $image = null,
        public ?string $author = null,
        public ?string $category = null,
        public ?string $source = null,
        public ?string $content = null,
    ) {}
}
