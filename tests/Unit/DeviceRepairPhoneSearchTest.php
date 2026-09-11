<?php

namespace Tests\Unit;

use App\Models\DeviceRepair;
use Tests\TestCase;

class DeviceRepairPhoneSearchTest extends TestCase
{
    /**
     * Search input with or without +20 should produce the same stored variants.
     */
    public function test_phone_variants_include_with_and_without_country_code(): void
    {
        $expected = [
            '+201226446623',
            '201226446623',
            '1226446623',
            '01226446623',
        ];

        foreach (['+201226446623', '201226446623', '1226446623', '01226446623'] as $input) {
            $variants = DeviceRepair::phoneSearchVariants($input);

            foreach ($expected as $form) {
                $this->assertContains(
                    $form,
                    $variants,
                    "Input [{$input}] should match stored form [{$form}]"
                );
            }
        }
    }

    /**
     * parsePhoneSearch should strip Egypt's country code even without a plus sign.
     */
    public function test_parse_phone_search_strips_egypt_country_code_without_plus(): void
    {
        [$countryCode, $national] = DeviceRepair::parsePhoneSearch('201226446623');

        $this->assertSame('+20', $countryCode);
        $this->assertSame('1226446623', $national);
    }
}
