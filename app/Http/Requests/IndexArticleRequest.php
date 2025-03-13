<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function hasAnySearchCriteria(): bool
    {
        return $this->filled('search') || $this->filled('source') || $this->filled('category');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'all' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max: 255'],
            'source' => ['nullable', 'string', 'max: 255'],
            'category' => ['nullable', 'string', 'max: 255'],
            'date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ];
    }
}
