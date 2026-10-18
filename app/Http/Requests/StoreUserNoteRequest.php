<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-user-history') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'note' => array('required', 'string', 'max:5000'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array(
            'note' => is_string($this->note) ? trim(strip_tags($this->note)) : $this->note,
        ));
    }
}
