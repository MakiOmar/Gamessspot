<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTraderOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-traders') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'opening_balance' => array('required', 'numeric', 'between:-999999999,999999999'),
            'opening_balance_date' => array('required', 'date'),
        );
    }
}
