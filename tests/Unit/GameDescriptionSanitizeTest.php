<?php

namespace Tests\Unit;

use App\Models\Game;
use Tests\TestCase;

class GameDescriptionSanitizeTest extends TestCase
{
    public function test_sanitize_description_strips_scripts_and_empty_editor_html(): void
    {
        $this->assertNull(Game::sanitizeDescription('<p><br></p>'));
        $this->assertNull(Game::sanitizeDescription(''));

        $clean = Game::sanitizeDescription('<p>Safe <strong>text</strong><script>alert(1)</script></p>');
        $this->assertSame('<p>Safe <strong>text</strong></p>', $clean);

        $cleanLink = Game::sanitizeDescription('<p><a href="javascript:alert(1)">x</a></p>');
        $this->assertStringNotContainsString('javascript:', $cleanLink);
    }
}
