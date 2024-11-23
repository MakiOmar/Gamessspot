<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CardController extends Controller
{
    /**
     * Display a listing of the cards.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cards = Card::with('category')->get();
        $categories = CardCategory::all(); // Retrieve all categories

        return view('manager.cards', compact('cards', 'categories'));
    }
    /**
     * Show the form for creating a new card.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return;
    }

    /**
     * Store a newly created card in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code' => 'required|string|unique:cards,code',
            'cost' => 'required|numeric',
            'card_category_id' => 'required|exists:card_categories,id',
        ]);

        $card = Card::create($validatedData);
        Cache::forget('total_code_cost'); // Clear the cache
        return response()->json([
            'success' => true,
            'message' => 'Card created successfully.',
            'data' => $card,
        ], 201); // 201 Created status code
    }


    /**
     * Show the specified card.
     *
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function show(Card $card)
    {
        return;
    }

    /**
     * Show the form for editing the specified card.
     *
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function edit(Card $card)
    {
        return;
    }


    /**
     * Update the specified card in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Card $card)
    {
        $validatedData = $request->validate([
        'code' => 'required|string|unique:cards,code,' . $card->id,
        'cost' => 'required|numeric',
        'card_category_id' => 'required|exists:card_categories,id',
        ]);

        $card->update($validatedData);

        return response()->json([
        'success' => true,
        'message' => 'Card updated successfully.',
        'data' => $card,
        ], 200); // 200 OK status code
    }


    /**
     * Remove the specified card from storage.
     *
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function destroy(Card $card)
    {
        $card->delete();
        return redirect()->route('cards.index')->with('success', 'Card deleted successfully.');
    }
}
