<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provider du firewall de renouvellement du token : choisit la source des utilisateurs
 * selon APP_AUTH_TYPE (ldap ou local), pour ne plus modifier security.yaml à la main.
 *
 * Mode LDAP : le compte doit toujours exister dans l'annuaire, un compte supprimé ou déplacé
 * ne peut donc plus renouveler son token.
 *
 * @implements UserProviderInterface<UserInterface>
 */
final class AuthModeUserProvider implements UserProviderInterface
{
    public function __construct(
        private LocalUserProvider $localProvider,
        #[Autowire(service: 'security.user.provider.concrete.ldap_server')]
        private UserProviderInterface $ldapProvider,
        #[Autowire('%app.auth_type%')]
        private string $authType
    ) {
    }

    private function provider(): UserProviderInterface
    {
        return 'ldap' === $this->authType ? $this->ldapProvider : $this->localProvider;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->provider()->loadUserByIdentifier($identifier);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->provider()->refreshUser($user);
    }

    public function supportsClass(string $class): bool
    {
        return $this->provider()->supportsClass($class);
    }
}
