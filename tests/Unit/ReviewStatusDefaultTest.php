<?php

namespace Tests\Unit;

use App\Models\Review;
use Tests\TestCase;

class ReviewStatusDefaultTest extends TestCase
{
    public function test_review_status_constants_default_to_pending(): void
    {
        $this->assertSame('pending', Review::STATUS_PENDING);
        $this->assertSame('approved', Review::STATUS_APPROVED);
        $this->assertSame('rejected', Review::STATUS_REJECTED);

        $review = new Review([
            'stars' => 5,
            'status' => Review::STATUS_PENDING,
        ]);

        $this->assertSame(Review::STATUS_PENDING, $review->status);
    }
}
