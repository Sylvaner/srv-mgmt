<?php

namespace App\Tests\Service;

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
}
