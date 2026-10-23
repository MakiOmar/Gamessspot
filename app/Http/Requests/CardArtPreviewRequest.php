<?php

namespace App\Http\Requests;

use App\Services\CardArt\CardArtCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Live preview of generated card artwork on the Next Load link form.
 */
class CardArtPreviewRequest extends FormRequest
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
        return array('name' => array('required', 'string', 'max:255')) + self::artRules();
    }

    /**
     * Optional overrides for the parsed brand, country and face value.
     *
     * @return array<string, mixed>
     */
    public static function artRules(): array
    {
        return array(
            'art_brand' => array('nullable', Rule::in(CardArtCatalog::brandKeys())),
            'art_country' => array('nullable', Rule::in(CardArtCatalog::countryKeys())),
            'art_value' => array('nullable', 'string', 'max:20'),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge(self::normalizedArt($this));
    }

    /**
     * @return array{art_brand: ?string, art_country: ?string, art_value: ?string}
     */
    public static function normalizedArt(FormRequest $request): array
    {
        $value = $request->input('art_value');

        return array(
            'art_brand' => $request->input('art_brand') ?: null,
            'art_country' => $request->input('art_country') ?: null,
            'art_value' => is_string($value) && trim($value) !== '' ? strip_tags(trim($value)) : null,
        );
    }
}
