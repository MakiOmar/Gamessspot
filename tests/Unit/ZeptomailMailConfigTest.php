<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Zoho CPaaS / ZeptoMail mailer registration (see docs/ZOHO_CPAAS_MAIL.md).
 */
class ZeptomailMailConfigTest extends TestCase
{
    public function test_zeptomail_mailer_is_registered(): void
    {
        $this->assertSame('zeptomail', config('mail.mailers.zeptomail.transport'));
    }

    public function test_services_zeptomail_reads_config_keys(): void
    {
        config([
            'services.zeptomail.host' => 'zoho.com',
            'services.zeptomail.token' => 'Zoho-enczapikey test',
        ]);

        $this->assertSame('zoho.com', config('services.zeptomail.host'));
        $this->assertSame('Zoho-enczapikey test', config('services.zeptomail.token'));
    }
}
