<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_issue' => 'required|in:None,Late delivery,wrong product,payment dispute,quality complaint',
        ];
    }

    public function messages(): array
    {
        return [
            'order_issue.required' => 'Order issue is required.',
            'order_issue.in' => 'Order issue must be one of the allowed types: None,Late delivery,wrong product,payment dispute,quality complaint',
        ];
    }
}
