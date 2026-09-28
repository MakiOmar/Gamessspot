<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
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
            'trader_id' => array('required', 'integer', 'exists:traders,id'),
            'purchase_date' => array('required', 'date'),
            'notes' => array('nullable', 'string', 'max:2000'),
            'items' => array('required', 'array', 'min:1', 'max:100'),
            'items.*.id' => array('nullable', 'integer'),
            'items.*.game_id' => array('required', 'integer', 'distinct', 'exists:games,id'),
            'items.*.quantity' => array('required', 'integer', 'min:1', 'max:100000'),
            'items.*.cost_per_account' => array('required', 'numeric', 'min:0', 'max:9999999'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array(
            'items.required' => 'Add at least one game line.',
            'items.*.game_id.distinct' => 'Each game can appear only once per purchase order.',
            'items.*.game_id.required' => 'Select a game for every line.',
        );
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->notes)) {
            $this->merge(array('notes' => strip_tags($this->notes)));
        }
    }
}
