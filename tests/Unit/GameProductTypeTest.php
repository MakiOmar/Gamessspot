<?php

namespace Tests\Unit;

use App\Models\Game;
use Tests\TestCase;

class GameProductTypeTest extends TestCase
{
    public function test_product_type_constants_and_normalize(): void
    {
        $this->assertSame('game', Game::TYPE_GAME);
        $this->assertSame('subscription', Game::TYPE_SUBSCRIPTION);
        $this->assertSame(Game::TYPE_GAME, Game::normalizeProductType(null));
        $this->assertSame(Game::TYPE_GAME, Game::normalizeProductType('invalid'));
        $this->assertSame(Game::TYPE_SUBSCRIPTION, Game::normalizeProductType('subscription'));
    }
}
