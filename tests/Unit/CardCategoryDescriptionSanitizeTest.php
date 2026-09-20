<?php

namespace Tests\Unit;

use App\Models\CardCategory;
use Tests\TestCase;

class CardCategoryDescriptionSanitizeTest extends TestCase
{
    public function test_sanitize_description_strips_scripts_and_empty_editor_html(): void
    {
        $this->assertNull(CardCategory::sanitizeDescription('<p><br></p>'));
        $this->assertNull(CardCategory::sanitizeDescription(''));

        $clean = CardCategory::sanitizeDescription('<p>Safe <strong>text</strong><script>alert(1)</script></p>');
        $this->assertSame('<p>Safe <strong>text</strong></p>', $clean);

        $cleanLink = CardCategory::sanitizeDescription('<p><a href="javascript:alert(1)">x</a></p>');
        $this->assertStringNotContainsString('javascript:', $cleanLink);
    }
}
