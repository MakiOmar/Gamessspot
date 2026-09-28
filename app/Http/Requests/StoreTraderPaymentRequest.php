<?php

namespace App\Http\Requests;

use App\Models\TraderPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTraderPaymentRequest extends FormRequest
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
            'amount' => array('required', 'numeric', 'min:0.01', 'max:999999999'),
            'payment_date' => array('required', 'date', 'before_or_equal:today'),
            'method' => array('required', Rule::in(array_keys(TraderPayment::METHODS))),
            'reference_number' => array('nullable', 'string', 'max:100'),
            'notes' => array('nullable', 'string', 'max:2000'),
            'attachment' => array('nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array(
            'payment_date.before_or_equal' => 'The payment date cannot be in the future.',
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array(
            'reference_number' => is_string($this->reference_number) ? strip_tags(trim($this->reference_number)) : $this->reference_number,
            'notes' => is_string($this->notes) ? strip_tags($this->notes) : $this->notes,
        ));
    }
}
