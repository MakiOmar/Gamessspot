<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoidTraderTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('void-trader-transactions') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'reason' => array('required', 'string', 'min:3', 'max:500'),
        );
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->reason)) {
            $this->merge(array('reason' => strip_tags(trim($this->reason))));
        }
    }
}
