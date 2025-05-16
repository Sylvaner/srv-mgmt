<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\LocalUserProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class LocalUserProviderTest extends KernelTestCase
{
    private ?LocalUserProvider $localUserProvider = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        
        // Create a temporary user for hashing passwords
        $user = new User();
        
        // Create a test provider with predefined users and hashed passwords
        $this->localUserProvider = new LocalUserProvider([
            'testuser' => [
                'password' => $this->passwordHasher->hashPassword($user, 'userpassword'),
                'roles' => ['ROLE_USER']
            ],
            'testadmin' => [
                'password' => $this->passwordHasher->hashPassword($user, 'adminpassword'),
                'roles' => ['ROLE_ADMIN']
            ]
        ], $this->passwordHasher);
    }

    public function testLoadExistingUser(): void
    {
        $user = $this->localUserProvider->loadUserByIdentifier('testuser');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->getLogin());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testLoadNonExistingUser(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->localUserProvider->loadUserByIdentifier('nonexistentuser');
    }

    public function testCheckValidCredentials(): void
    {
        $result = $this->localUserProvider->checkCredentials('testuser', 'userpassword');
        $this->assertTrue($result);
    }

    public function testCheckInvalidPassword(): void
    {
        $result = $this->localUserProvider->checkCredentials('testuser', 'wrongpassword');
        $this->assertFalse($result);
    }

    public function testCheckInvalidUsername(): void
    {
        $result = $this->localUserProvider->checkCredentials('nonexistentuser', 'anypassword');
        $this->assertFalse($result);
    }
    
    public function testSupportsClass(): void
    {
        $this->assertTrue($this->localUserProvider->supportsClass(User::class));
        $this->assertFalse($this->localUserProvider->supportsClass(\stdClass::class));
    }
    
    public function testRefreshUser(): void
    {
        $originalUser = $this->localUserProvider->loadUserByIdentifier('testuser');
        $refreshedUser = $this->localUserProvider->refreshUser($originalUser);
        
        $this->assertEquals($originalUser->getLogin(), $refreshedUser->getLogin());
        $this->assertEquals($originalUser->getRoles(), $refreshedUser->getRoles());
    }
}
