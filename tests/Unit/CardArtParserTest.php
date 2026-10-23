<?php

namespace Tests\Unit;

use App\Services\CardArt\CardArtParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CardArtParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    public static function names(): array
    {
        return [
            'next load docs, code currency' => ['amazon KSA 100 SAR', 'amazon', '100 SAR', 'sa'],
            'next load docs, symbol after amount' => ['Amazon UK 10 £', 'amazon', '£10', 'gb'],
            'symbol before amount' => ['PSN US $10', 'playstation', '$10', 'us'],
            'symbol after amount' => ['Playstation 10$', 'playstation', '$10', 'us'],
            'country from currency' => ['iTunes 50 AED', 'apple', '50 AED', 'ae'],
            'code before amount' => ['Google Play USD 25', 'googleplay', '$25', 'us'],
            'decimals trimmed' => ['Steam 20.00 EUR', 'steam', '€20', 'eu'],
            'thousands separator' => ['Xbox TR 1,000 TRY', 'xbox', '1000 TRY', 'tr'],
            'global has no country' => ['Razer Gold Global 25 USD', 'generic', '$25', null],
            'lowercase two letters are not a country' => ['nintendo eshop for us 10 GBP', 'nintendo', '£10', 'gb'],
            'no face value' => ['Test', 'generic', null, null],
        ];
    }

    #[DataProvider('names')]
    public function test_parses_brand_value_and_country(string $name, string $brand, ?string $value, ?string $country): void
    {
        $parsed = (new CardArtParser)->parse($name);

        $this->assertSame($brand, $parsed['brand']);
        $this->assertSame($value, $parsed['value']);
        $this->assertSame($country, $parsed['country']);
    }

    public function test_unknown_brand_uses_the_name_without_value_and_country(): void
    {
        $parsed = (new CardArtParser)->parse('Razer Gold Global 25 USD');

        $this->assertSame('Razer Gold', $parsed['label']);
        $this->assertTrue($parsed['global']);
    }

    public function test_known_brand_uses_its_label(): void
    {
        $this->assertSame('PlayStation Store', (new CardArtParser)->parse('PSN US $10')['label']);
    }
}
