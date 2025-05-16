<?php

namespace App\Service;

use App\Security\LocalUserProvider;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LocalAuthService
{
    private LocalUserProvider $localUserProvider;

    public function __construct(LocalUserProvider $localUserProvider)
    {
        $this->localUserProvider = $localUserProvider;
    }

    /**
     * Verify local user credentials
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return bool Whether credentials are valid
     * @throws AuthenticationException If authentication fails
     */
    public function checkCredentials(string $username, string $password): bool
    {
        try {
            $this->localUserProvider->loadUserByIdentifier($username);

            if (!$this->localUserProvider->checkCredentials($username, $password)) {
                throw new AuthenticationException('Invalid password');
            }

            return true;
        } catch (\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
            throw new AuthenticationException('User not found or invalid credentials', 0, $e);
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentication error', 0, $e);
        }
    }
}
