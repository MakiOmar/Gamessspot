<?php

namespace Tests\Feature;

use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Tests\TestCase;

class BackupDumpSslTest extends TestCase
{
    protected function credentialsFor(array $dumpConfig): string
    {
        config()->set('database.connections.mysql.dump', $dumpConfig);

        return DbDumperFactory::createFromConnection('mysql')->getContentsOfCredentialsFile();
    }

    public function test_skip_ssl_disables_tls_for_mysqldump(): void
    {
        $this->assertMatchesRegularExpression('/^(skip-ssl|ssl-mode=DISABLED)$/m', $this->credentialsFor(array('skip_ssl' => true)));
    }

    public function test_dump_keeps_tls_when_flag_is_absent(): void
    {
        $this->assertDoesNotMatchRegularExpression('/^(skip-ssl|ssl-mode=DISABLED)$/m', $this->credentialsFor(array()));
    }

    public function test_default_config_omits_skip_ssl_key(): void
    {
        $this->assertArrayNotHasKey('skip_ssl', config('database.connections.mysql.dump'));
    }
}
