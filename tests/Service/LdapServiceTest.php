<?php

namespace App\Tests\Service;

use App\Service\LdapAdapterFactory;
use App\Service\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LdapServiceTest extends KernelTestCase
{
    private $ldapService = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->ldapService = $container->get(LdapService::class);
    }

    public function testLdapWithBadPassword(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid password');
        $this->ldapService->checkCredentials('adminuser1', 'test_password');
    }

    public function testLdapWithBadUser(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('LDAP entry not found uid=false_user');
        $user = $this->ldapService->checkCredentials('false_user', 'password');
        $this->assertNull($user);
    }

    public function testLdapConnection(): void
    {
        $user = $this->ldapService->checkCredentials('adminuser1', 'password');
        $this->assertEquals('uid=adminuser1,ou=people,dc=ldapmock,dc=local', $user->getDn());
    }

    public function testLdapWithEmptyPassword(): void
    {
        // Un bind avec un mot de passe vide est un bind anonyme : il ne doit jamais être tenté
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid password');
        $this->ldapService->checkCredentials('adminuser1', '');
    }

    public function testLdapFilterIsEscaped(): void
    {
        // Sans échappement, "*" correspondrait à tous les utilisateurs
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('LDAP entry not found');
        $this->ldapService->checkCredentials('*', 'password');
    }

    public function testLdapSeveralCallsWithSameService(): void
    {
        // Le bind utilisateur change l'identité de la connexion : le compte de service doit être reconnecté
        $first = $this->ldapService->checkCredentials('adminuser1', 'password');
        $second = $this->ldapService->checkCredentials('adminuser1', 'password');
        $this->assertEquals($first->getDn(), $second->getDn());
    }

    public function testLdaps(): void
    {
        $host = $_ENV['LDAP_HOST'] ?? 'localhost';
        $socket = @fsockopen($host, 636, $errno, $error, 2);
        if (false === $socket) {
            $this->markTestSkipped("LDAPS indisponible sur $host:636");
        }
        fclose($socket);

        // Le serveur de test utilise un certificat auto-signé
        $adapter = LdapAdapterFactory::create($host, 636, 'ssl', 3, requireCert: 'never');
        $service = new LdapService(
            $adapter,
            $_ENV['LDAP_BASE_SEARCH_DN'],
            $_ENV['LDAP_USER_KEY'],
            $_ENV['LDAP_USER_DN'],
            $_ENV['LDAP_USER_PASSWORD']
        );
        $user = $service->checkCredentials('adminuser1', 'password');
        $this->assertEquals('uid=adminuser1,ou=people,dc=ldapmock,dc=local', $user->getDn());
    }
}
