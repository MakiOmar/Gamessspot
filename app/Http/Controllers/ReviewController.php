<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\Game;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Reviews management page.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', Review::STATUS_PENDING);
        $type = $request->input('type'); // game|card_category|empty
        $search = $request->input('search');

        $query = Review::with(['user:id,name,phone', 'reviewable'])
            ->orderByDesc('created_at');

        if (in_array($status, [Review::STATUS_PENDING, Review::STATUS_APPROVED, Review::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        } elseif ($status !== 'all') {
            $query->where('status', Review::STATUS_PENDING);
            $status = Review::STATUS_PENDING;
        }

        if ($type === 'game') {
            $query->where('reviewable_type', (new Game())->getMorphClass());
        } elseif ($type === 'card_category') {
            $query->where('reviewable_type', (new CardCategory())->getMorphClass());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $reviews = $query->paginate(20)->appends($request->all());

        $statusCounts = [
            'pending' => Review::pending()->count(),
            'approved' => Review::approved()->count(),
            'rejected' => Review::where('status', Review::STATUS_REJECTED)->count(),
            'all' => Review::count(),
        ];

        return view('manager.reviews.index', compact('reviews', 'status', 'type', 'search', 'statusCounts'));
    }

    public function approve(Review $review)
    {
        $review->update([
            'status' => Review::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review approved.',
            'status' => $review->status,
        ]);
    }

    public function reject(Review $review)
    {
        $review->update([
            'status' => Review::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review rejected.',
            'status' => $review->status,
        ]);
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted.',
        ]);
    }
}
