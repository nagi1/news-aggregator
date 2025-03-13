<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserPreferenceFactory> */
    use HasFactory;

    protected function casts()
    {
        return [
            'sources' => 'array',
            'categories' => 'array',
            'authors' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
