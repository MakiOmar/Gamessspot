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

    /**
     * Non-Egypt calling codes must parse correctly with or without +.
     */
    public function test_parse_phone_search_supports_any_country_code(): void
    {
        [$saudiCode, $saudiNational] = DeviceRepair::parsePhoneSearch('+966501234567');
        $this->assertSame('+966', $saudiCode);
        $this->assertSame('501234567', $saudiNational);

        [$saudiCode, $saudiNational] = DeviceRepair::parsePhoneSearch('966501234567');
        $this->assertSame('+966', $saudiCode);
        $this->assertSame('501234567', $saudiNational);

        [$ukCode, $ukNational] = DeviceRepair::parsePhoneSearch('+447911123456');
        $this->assertSame('+44', $ukCode);
        $this->assertSame('7911123456', $ukNational);

        [$usCode, $usNational] = DeviceRepair::parsePhoneSearch('+15551234567');
        $this->assertSame('+1', $usCode);
        $this->assertSame('5551234567', $usNational);
    }

    /**
     * Variants for a non-Egypt number include with and without that country code.
     */
    public function test_phone_variants_work_for_non_egypt_country_codes(): void
    {
        $expected = [
            '+966501234567',
            '966501234567',
            '501234567',
            '0501234567',
        ];

        foreach (['+966501234567', '966501234567'] as $input) {
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
}
