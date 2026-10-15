<?php

namespace App\Http\Requests;

use App\Models\Trader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForceDeleteTraderRequest extends FormRequest
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
        $trader = $this->route('trader');
        $name = $trader instanceof Trader ? trim((string) $trader->name) : '';

        return array(
            'reason' => array('required', 'string', 'min:3', 'max:500'),
            'confirm_name' => array('required', 'string', Rule::in(array($name))),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array(
            'confirm_name.in' => 'The typed name does not match the trader name.',
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array(
            'reason' => is_string($this->reason) ? strip_tags(trim($this->reason)) : $this->reason,
            'confirm_name' => is_string($this->confirm_name) ? trim($this->confirm_name) : $this->confirm_name,
        ));
    }
}
