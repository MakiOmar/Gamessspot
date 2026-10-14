<?php

namespace App\Services;

use App\Models\Trader;

class TraderService
{
    public function __construct(private SystemActivityLogger $activityLogger)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $actorId): Trader
    {
        $trader = Trader::create(array_merge($this->normalize($data), array(
            'created_by' => $actorId,
            'updated_by' => $actorId,
        )));

        $this->activityLogger->log('trader.created', 'trader', $trader->id, $trader->name);

        return $trader;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Trader $trader, array $data, ?int $actorId): Trader
    {
        $trader->fill($this->normalize($data));
        $trader->updated_by = $actorId;

        $changes = array_keys($trader->getDirty());
        $original = array_intersect_key($trader->getOriginal(), array_flip($changes));
        $trader->save();

        if (in_array('name', $changes, true)) {
            CacheManager::invalidateAccounts();
        }

        if (array_diff($changes, array('updated_by')) !== array()) {
            $this->activityLogger->log('trader.updated', 'trader', $trader->id, $trader->name, array(
                'changed' => array_values(array_diff($changes, array('updated_by', 'updated_at'))),
                'opening_balance_before' => array_key_exists('opening_balance', $original) ? (string) $original['opening_balance'] : null,
            ));
        }

        return $trader;
    }

    public function updateOpeningBalance(Trader $trader, float $amount, ?string $date, ?int $actorId): Trader
    {
        $before = (string) $trader->opening_balance;

        $trader->update(array(
            'opening_balance' => round($amount, 2),
            'opening_balance_date' => $date,
            'updated_by' => $actorId,
        ));

        $this->activityLogger->log('trader.opening_balance_updated', 'trader', $trader->id, $trader->name, array(
            'before' => $before,
            'after' => (string) $trader->opening_balance,
            'date' => $date,
        ));

        return $trader;
    }

    /**
     * Reasons a trader cannot be deleted; empty when deletion is safe.
     * Voided purchase orders and payments still count: they are part of the trader's history.
     *
     * @return array<int, string>
     */
    public function deletionBlockers(Trader $trader): array
    {
        $blockers = array();

        $purchaseOrders = $trader->purchaseOrders()->count();
        if ($purchaseOrders > 0) {
            $blockers[] = "Has {$purchaseOrders} purchase order(s).";
        }

        $accounts = $trader->accounts()->count();
        if ($accounts > 0) {
            $blockers[] = "Has {$accounts} linked game account(s).";
        }

        $payments = $trader->payments()->count();
        if ($payments > 0) {
            $blockers[] = "Has {$payments} payment(s).";
        }

        return $blockers;
    }

    /**
     * @return array<int, string> Blocking reasons; the trader is deleted only when this is empty.
     */
    public function delete(Trader $trader): array
    {
        $blockers = $this->deletionBlockers($trader);
        if ($blockers !== array()) {
            return $blockers;
        }

        $this->activityLogger->log('trader.deleted', 'trader', $trader->id, $trader->name);
        $trader->delete();
        CacheManager::invalidateAccounts();

        return array();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $normalized = array(
            'name' => trim((string) $data['name']),
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? Trader::STATUS_ACTIVE,
        );

        if (array_key_exists('opening_balance', $data)) {
            $normalized['opening_balance'] = round((float) ($data['opening_balance'] ?? 0), 2);
            $normalized['opening_balance_date'] = $data['opening_balance_date'] ?? null;
        }

        return $normalized;
    }
}
