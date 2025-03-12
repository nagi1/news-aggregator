<?php

namespace App\Models;

use App\Enums\NewsProviderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    protected function casts()
    {
        return [
            'published_at' => 'datetime',
            'api_provider' => NewsProviderEnum::class,
        ];
    }
}
