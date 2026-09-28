<?php

namespace App\Http\Requests;

use App\Models\Trader;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TraderRequest extends FormRequest
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
        $rules = array(
            'name' => array('required', 'string', 'max:255'),
            'phone' => array('nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'),
            'whatsapp' => array('nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]*$/'),
            'notes' => array('nullable', 'string', 'max:2000'),
            'status' => array('required', Rule::in(Trader::STATUSES)),
        );

        if ($this->isMethod('post')) {
            $rules['opening_balance'] = array('nullable', 'numeric', 'between:-999999999,999999999');
            $rules['opening_balance_date'] = array('nullable', 'date', 'required_with:opening_balance');
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array(
            'name' => is_string($this->name) ? strip_tags(trim($this->name)) : $this->name,
            'notes' => is_string($this->notes) ? strip_tags($this->notes) : $this->notes,
        ));
    }
}
