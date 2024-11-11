<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardCategory;
use Illuminate\Http\Request;

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
        return view('cards.index', compact('cards'));
    }

    /**
     * Show the form for creating a new card.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = CardCategory::all();
        return view('cards.create', compact('categories'));
    }

    /**
     * Store a newly created card in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:cards,code',
            'cost' => 'required|numeric',
            'card_category_id' => 'required|exists:card_categories,id',
        ]);

        Card::create($request->only('code', 'cost', 'card_category_id'));

        return redirect()->route('cards.index')->with('success', 'Card created successfully.');
    }

    /**
     * Show the specified card.
     *
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function show(Card $card)
    {
        return view('cards.show', compact('card'));
    }

    /**
     * Show the form for editing the specified card.
     *
     * @param  \App\Models\Card  $card
     * @return \Illuminate\Http\Response
     */
    public function edit(Card $card)
    {
        $categories = CardCategory::all();
        return view('cards.edit', compact('card', 'categories'));
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
        $request->validate([
            'code' => 'required|string|unique:cards,code,' . $card->id,
            'cost' => 'required|numeric',
            'card_category_id' => 'required|exists:card_categories,id',
        ]);

        $card->update($request->only('code', 'cost', 'card_category_id'));

        return redirect()->route('cards.index')->with('success', 'Card updated successfully.');
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
