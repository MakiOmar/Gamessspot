<?php

namespace App\Http\Requests;

use App\Models\CardCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Link a card category to a Next Load product (new link may create the category).
 */
class NextLoadLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-next-load') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = array(
            'next_load_product_id' => array('required', 'integer', 'min:1'),
            'supplier_mode' => array('required', Rule::in(CardCategory::SUPPLIER_MODES)),
            'supplier_daily_limit' => array('nullable', 'integer', 'min:1', 'max:1000'),
        ) + CardArtPreviewRequest::artRules();

        if ($this->isMethod('post')) {
            $rules['card_category_id'] = array('nullable', 'integer', 'exists:card_categories,id', 'required_without:name');
            $rules['name'] = array('nullable', 'string', 'max:255', 'required_without:card_category_id', 'unique:card_categories,name');
            $rules['price'] = array('nullable', 'numeric', 'min:0', 'required_with:name');
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array(
            'name' => is_string($this->name) && trim($this->name) !== '' ? strip_tags(trim($this->name)) : null,
            'card_category_id' => $this->card_category_id ?: null,
            'supplier_daily_limit' => $this->supplier_daily_limit === '' ? null : $this->supplier_daily_limit,
        ) + CardArtPreviewRequest::normalizedArt($this));
    }
}
