<?php

namespace App\Tests\Service;

use App\Service\LdapAdapterFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

class LdapAdapterFactoryTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function getConfig(Adapter $adapter): array
    {
        $connection = $adapter->getConnection();
        $property = new \ReflectionProperty($connection, 'config');

        return $property->getValue($connection);
    }

    public function testPlainConnection(): void
    {
        $config = $this->getConfig(LdapAdapterFactory::create('ldap.local', 389, 'none', 3));
        $this->assertSame('ldap://ldap.local:389', $config['connection_string']);
        $this->assertArrayNotHasKey('x_tls_require_cert', $config['options']);
    }

    public function testLdaps(): void
    {
        $config = $this->getConfig(LdapAdapterFactory::create('ldap.local', 636, 'ssl', 3));
        $this->assertSame('ldaps://ldap.local:636', $config['connection_string']);
        // Le certificat est vérifié par défaut
        $this->assertSame(LDAP_OPT_X_TLS_DEMAND, $config['options']['x_tls_require_cert']);
        $this->assertArrayNotHasKey('x_tls_cacertfile', $config['options']);
    }

    public function testStartTls(): void
    {
        $config = $this->getConfig(LdapAdapterFactory::create('ldap.local', 389, 'tls', 3));
        $this->assertSame('ldap://ldap.local:389', $config['connection_string']);
        $this->assertSame('tls', $config['encryption']);
    }

    public function testCustomCertificateOptions(): void
    {
        $config = $this->getConfig(
            LdapAdapterFactory::create('ldap.local', 636, 'ssl', 3, '/etc/ssl/ca.pem', '/etc/ssl/ca', 'never')
        );
        $this->assertSame('/etc/ssl/ca.pem', $config['options']['x_tls_cacertfile']);
        $this->assertSame('/etc/ssl/ca', $config['options']['x_tls_cacertdir']);
        $this->assertSame(LDAP_OPT_X_TLS_NEVER, $config['options']['x_tls_require_cert']);
    }

    public function testEmptyCertificateOptionsAreIgnored(): void
    {
        $config = $this->getConfig(LdapAdapterFactory::create('ldap.local', 636, 'ssl', 3, '', '', ''));
        $this->assertArrayNotHasKey('x_tls_cacertfile', $config['options']);
        $this->assertArrayNotHasKey('x_tls_cacertdir', $config['options']);
        $this->assertSame(LDAP_OPT_X_TLS_DEMAND, $config['options']['x_tls_require_cert']);
    }

    public function testInvalidRequireCert(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LdapAdapterFactory::create('ldap.local', 636, 'ssl', 3, null, null, 'invalid');
    }

    public function testDefaultPortFollowsEncryption(): void
    {
        $ssl = $this->getConfig(LdapAdapterFactory::create('ldap.local', null, 'ssl', null));
        $this->assertSame('ldaps://ldap.local:636', $ssl['connection_string']);
        $this->assertSame(3, $ssl['version']);
        $plain = $this->getConfig(LdapAdapterFactory::create('ldap.local', 0, 'none', 0));
        $this->assertSame('ldap://ldap.local:389', $plain['connection_string']);
    }
}
