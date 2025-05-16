<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

// @phpstan-ignore-next-line
class LocalUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @var array<string, array{password: string, roles: array<string>}>
     */
    private array $users;
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @param array<string, array{password: string, roles: array<string>}> $users
     */
    public function __construct(array $users, UserPasswordHasherInterface $passwordHasher)
    {
        $this->users = $users;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if (!isset($this->users[$identifier])) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        $userData = $this->users[$identifier];

        $user = new User();
        $user->setLogin($identifier);
        $user->setRoles($userData['roles']);
        $user->setActive(true);
        $user->setLastLogin(new \DateTime());

        return $user;
    }

    /**
     * Check if the provided password is valid for this user
     */
    public function checkCredentials(string $username, string $password): bool
    {
        if (!isset($this->users[$username])) {
            return false;
        }

        $hashedPassword = $this->users[$username]['password'];

        if (!str_starts_with($hashedPassword, '$')) {
            throw new \LogicException(
                sprintf(
                    'Password for user "%s" is not hashed. Use the hash-password command to generate secure passwords.',
                    $username
                )
            );
        }

        $testUser = new class ($username, $hashedPassword) implements PasswordAuthenticatedUserInterface {
            private string $username;
            private string $password;

            public function __construct(string $username, string $password)
            {
                $this->username = $username;
                $this->password = $password;
            }

            public function getPassword(): string
            {
                return $this->password;
            }

            public function getUserIdentifier(): string
            {
                return $this->username;
            }
        };

        return $this->passwordHasher->isPasswordValid($testUser, $password);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // This is used to update the password hash in the database when the password hasher changes
        // Since our users are defined in configuration, we don't need to update them
    }
}
