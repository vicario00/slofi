<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'raw_text' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }
}
