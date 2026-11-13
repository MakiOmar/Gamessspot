<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class ExportOrdersRequest extends FormRequest
{
    public const REPORT_STATUSES = array('needs_return', 'has_problem', 'solved', 'archived');

    public function authorize(): bool
    {
        return $this->user('admin')?->can('manage-sell-log') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array(
            'searchOrder' => array('nullable', 'string', 'max:255'),
            'status'      => array('nullable', 'string', 'in:all,' . implode(',', self::REPORT_STATUSES)),
            'start_date'  => array('nullable', 'date_format:Y-m-d'),
            'end_date'    => array('nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'),
            'id'          => array('nullable', 'integer', 'min:1'),
            'seller_id'   => array('nullable', 'regex:/^(' . Order::WEBSITE_SELLER . '|[1-9][0-9]*)$/'),
        );
    }

    /**
     * Filters for {@see \App\Exports\OrdersExport}, with empty values removed.
     *
     * @return array{search?: string, status?: string, start_date?: string, end_date?: string, store_profile_id?: int, seller_id?: int|string}
     */
    public function filters(): array
    {
        $data = $this->validated();
        $seller = $data['seller_id'] ?? null;

        return array_filter(array(
            'search'           => isset($data['searchOrder']) ? trim($data['searchOrder']) : null,
            'status'           => ($data['status'] ?? 'all') === 'all' ? null : $data['status'],
            'start_date'       => $data['start_date'] ?? null,
            'end_date'         => $data['end_date'] ?? null,
            'store_profile_id' => isset($data['id']) ? (int) $data['id'] : null,
            'seller_id'        => $seller === null || $seller === Order::WEBSITE_SELLER ? $seller : (int) $seller,
        ), fn ($value) => $value !== null && $value !== '');
    }
}
