<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\Game;
use App\Models\Review;
use App\Support\PhoneUserResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicReviewController extends Controller
{
    /**
     * Public API: submit a star review with optional comment (pending until approved).
     */
    public function store(Request $request)
    {
        $phone = $request->input('phone_number', $request->input('phone'));
        $request->merge(['phone' => $phone]);

        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'game_id' => [
                'nullable',
                'integer',
                'exists:games,id',
                Rule::requiredIf(!$request->filled('card_category_id')),
            ],
            'card_category_id' => [
                'nullable',
                'integer',
                'exists:card_categories,id',
                Rule::requiredIf(!$request->filled('game_id')),
            ],
        ]);

        if ($request->filled('game_id') && $request->filled('card_category_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Provide either game_id or card_category_id, not both.',
            ], 422);
        }

        $reviewable = $request->filled('game_id')
            ? Game::findOrFail($validated['game_id'])
            : CardCategory::findOrFail($validated['card_category_id']);

        $user = PhoneUserResolver::findOrCreateCustomer($validated['phone']);

        $existing = Review::query()
            ->where('user_id', $user->id)
            ->where('reviewable_type', $reviewable->getMorphClass())
            ->where('reviewable_id', $reviewable->getKey())
            ->first();

        if ($existing && $existing->status === Review::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an approved review for this product.',
                'data' => [
                    'id' => $existing->id,
                    'stars' => $existing->stars,
                    'status' => $existing->status,
                ],
            ], 409);
        }

        $payload = [
            'stars' => (int) $validated['stars'],
            'comment' => $validated['comment'] ?? null,
            'status' => Review::STATUS_PENDING,
            'reviewed_at' => null,
        ];

        if ($existing) {
            $existing->update($payload);
            $review = $existing->fresh();
            $statusCode = 200;
            $message = 'Review updated and pending approval.';
        } else {
            $review = $reviewable->reviews()->create(array_merge($payload, [
                'user_id' => $user->id,
            ]));
            $statusCode = 201;
            $message = 'Review submitted and pending approval.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $review->id,
                'stars' => $review->stars,
                'comment' => $review->comment,
                'status' => $review->status,
                'reviewable_type' => class_basename($review->reviewable_type),
                'reviewable_id' => $review->reviewable_id,
            ],
        ], $statusCode);
    }
}
