<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NextLoadRestockRequest extends FormRequest
{
    /** Upper bound per restock call so one click cannot drain the supplier balance. */
    public const MAX_QUANTITY = 50;

    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-next-load') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'quantity' => array('required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITY),
        );
    }
}
