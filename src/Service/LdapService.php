<?php

namespace App\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LdapService
{
    private Ldap $ldap;

    public function __construct(
        Adapter $ldapAdapter,
        private string $ldapBaseSearch,
        private string $ldapUserKey,
        private string $ldapUser,
        private string $ldapPassword
    ) {
        // La connexion est établie au premier bind (lazy) et réutilisée pour toutes les opérations
        $this->ldap = new Ldap($ldapAdapter);
    }

    /**
     * Vérifie les identifiants LDAP.
     *
     * Une authentification coûte 1 connexion et 3 opérations : bind du compte de service,
     * recherche du DN de l'utilisateur, bind avec le mot de passe saisi.
     *
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     *
     * @return Entry Entrée LDAP de l'utilisateur
     * @throws AuthenticationException si l'authentification échoue
     */
    public function checkCredentials(string $username, string $password): Entry
    {
        // Un bind avec un mot de passe vide est un bind anonyme, accepté par certains serveurs LDAP
        if ('' === $username || '' === $password) {
            throw new AuthenticationException('Invalid password');
        }

        try {
            // Le bind est refait à chaque appel : le bind utilisateur précédent a changé
            // l'identité de la connexion (service maintenu en mémoire, plusieurs appels).
            $this->ldap->bind($this->ldapUser, $this->ldapPassword);

            // Cette étape doit être réalisée avant car il faut le DN du compte pour tester le mot de passe
            $entries = $this->ldap->query(
                $this->ldapBaseSearch,
                sprintf(
                    '(&(objectClass=person)(%s=%s))',
                    $this->ldapUserKey,
                    $this->ldap->escape($username, '', LdapInterface::ESCAPE_FILTER)
                ),
                // Deux résultats suffisent pour détecter un identifiant ambigu
                ['maxItems' => 2]
            )->execute()->toArray();
        } catch (ConnectionException $e) {
            throw new AuthenticationException('LDAP connection failed: ' . $e->getMessage());
        }

        if (empty($entries)) {
            throw new AuthenticationException('LDAP entry not found ' . $this->ldapUserKey . '=' . $username);
        }
        if (count($entries) > 1) {
            throw new AuthenticationException('LDAP entry not unique ' . $this->ldapUserKey . '=' . $username);
        }
        $entry = $entries[0];

        // Test du mot de passe en établissant une connexion avec le compte de l'utilisateur
        try {
            $this->ldap->bind($entry->getDn(), $password);
        } catch (InvalidCredentialsException) {
            throw new AuthenticationException('Invalid password');
        } catch (ConnectionException $e) {
            throw new AuthenticationException('LDAP connection failed: ' . $e->getMessage());
        }

        return $entry;
    }
}
