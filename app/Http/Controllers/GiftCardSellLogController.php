<?php

namespace App\Http\Controllers;

use App\Http\Requests\GiftCardSellLogRequest;
use App\Models\CardCategory;
use App\Models\StoresProfile;
use App\Services\GiftCardSellLogService;
use Illuminate\View\View;

/**
 * Gift card sales only (local and Next Load codes); the main Sell Log keeps listing them too.
 */
class GiftCardSellLogController extends Controller
{
    public function __construct(private readonly GiftCardSellLogService $sellLog)
    {
    }

    public function index(GiftCardSellLogRequest $request): View
    {
        $user = $request->user('admin');
        $filters = $request->filters();

        return view('manager.gift-cards.sell-log', array(
            'orders'        => $this->sellLog->paginate($user, $filters),
            'totals'        => $this->sellLog->totals($user, $filters),
            'filters'       => $filters,
            'storeProfiles' => StoresProfile::orderBy('name')->get(array('id', 'name')),
            'categories'    => CardCategory::orderBy('name')->get(array('id', 'name')),
            'showCost'      => $user->hasRole('admin'),
            'showCode'      => ! $user->hasRole('accountant'),
        ));
    }
}
