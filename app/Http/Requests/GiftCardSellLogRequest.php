<?php

namespace App\Http\Requests;

use App\Models\Card;
use Illuminate\Foundation\Http\FormRequest;

class GiftCardSellLogRequest extends FormRequest
{
    public const SOURCES = array(Card::SOURCE_MANUAL, Card::SOURCE_NEXT_LOAD);

    public function authorize(): bool
    {
        $user = $this->user('admin');

        return $user !== null && $user->can('view-sell-log') && $user->can('manage-gift-cards');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'q'                => array('nullable', 'string', 'max:255'),
            'start_date'       => array('nullable', 'date_format:Y-m-d'),
            'end_date'         => array('nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'),
            'store_profile_id' => array('nullable', 'integer', 'min:1'),
            'card_category_id' => array('nullable', 'integer', 'min:1'),
            'source'           => array('nullable', 'string', 'in:all,' . implode(',', self::SOURCES)),
        );
    }

    /**
     * Filters for {@see \App\Services\GiftCardSellLogService}; the date range defaults to today.
     *
     * @return array{q: string, start_date: string, end_date: string, store_profile_id: ?int, card_category_id: ?int, source: string}
     */
    public function filters(): array
    {
        $data = $this->validated();
        $today = now()->toDateString();

        return array(
            'q'                => trim((string) ($data['q'] ?? '')),
            'start_date'       => $data['start_date'] ?? $today,
            'end_date'         => $data['end_date'] ?? ($data['start_date'] ?? $today),
            'store_profile_id' => isset($data['store_profile_id']) ? (int) $data['store_profile_id'] : null,
            'card_category_id' => isset($data['card_category_id']) ? (int) $data['card_category_id'] : null,
            'source'           => $data['source'] ?? 'all',
        );
    }
}
