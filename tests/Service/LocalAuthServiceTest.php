<?php

namespace App\Tests\Service;

use App\Security\LocalUserProvider;
use App\Service\LocalAuthService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LocalAuthServiceTest extends KernelTestCase
{
    private ?LocalAuthService $localAuthService = null;
    private ?LocalUserProvider $localUserProvider = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        
        $tempUser = new \App\Entity\User();
        
        $this->localUserProvider = new LocalUserProvider([
            'testuser' => [
                'password' => $passwordHasher->hashPassword($tempUser, 'userpassword'),
                'roles' => ['ROLE_USER']
            ],
            'testadmin' => [
                'password' => $passwordHasher->hashPassword($tempUser, 'adminpassword'),
                'roles' => ['ROLE_ADMIN']
            ]
        ], $passwordHasher);
        
        $this->localAuthService = new LocalAuthService($this->localUserProvider);
    }

    public function testValidCredentials(): void
    {
        $result = $this->localAuthService->checkCredentials('testuser', 'userpassword');
        $this->assertTrue($result);
    }

    public function testInvalidPassword(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid password');
        $this->localAuthService->checkCredentials('testuser', 'wrongpassword');
    }

    public function testNonExistentUser(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User not found or invalid credentials');
        $this->localAuthService->checkCredentials('nonexistentuser', 'anypassword');
    }
}
