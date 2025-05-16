<?php

namespace App\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LdapService
{
    private Adapter $ldapAdapter;
    private string $ldapBaseSearch;
    private Ldap $ldap;
    private string $ldapUserKey;
    private string $ldapUser;
    private string $ldapPassword;
    private bool $isConnected = false;

    public function __construct(
        Adapter $ldapAdapter,
        string $ldapBaseSearch,
        Ldap $ldap,
        string $ldapUserKey,
        string $ldapUser,
        string $ldapPassword
    ) {
        $this->ldapAdapter = $ldapAdapter;
        $this->ldapBaseSearch = $ldapBaseSearch;
        $this->ldap = $ldap;
        $this->ldapUserKey = $ldapUserKey;
        $this->ldapUser = $ldapUser;
        $this->ldapPassword = $ldapPassword;
        // Ne pas se connecter ici - attendre le premier appel
    }

    /**
     * Établit la connexion LDAP si ce n'est pas déjà fait
     *
     * @return void
     * @throws ConnectionException si la connexion échoue
     */
    private function ensureConnected(): void
    {
        if (!$this->isConnected) {
            $this->ldap->bind($this->ldapUser, $this->ldapPassword);
            $this->isConnected = true;
        }
    }

    /**
     * Vérifie les identifiants LDAP
     *
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     *
     * @return Entry|null Entrée LDAP ou null si échec
     */
    public function checkCredentials(string $username, string $password): ?Entry
    {
        try {
            // S'assurer que la connexion est établie
            $this->ensureConnected();

            $ldap = new Ldap($this->ldapAdapter);
            $search = [];
            $value = null;
            // Recherche si l'utilisateur existe
            // Cette étape doit être réalisée avant car il faut le DN du compte pour tester le mot de passe
            $search = $ldap->query(
                $this->ldapBaseSearch,
                // userAccountControl=512 -> Le mot de passe n'est pas à changer et le compte est activé
                '(&(objectClass=person)(' . $this->ldapUserKey . '=' . $username . '))'
            )->execute()->toArray();
            // Vérifie qu'il y a un résultat unique
            if (empty($search)) {
                throw new AuthenticationException('LDAP entry not found ' . $this->ldapUserKey . '=' . $username);
            }
            $value = $search[0];
            // Test du mot de passe en établissant une connexion
            try {
                $ldap->bind($value->getDn(), $password);
            } catch (ConnectionException) {
                throw new AuthenticationException('Invalid password');
            }

            return $value;
        } catch (ConnectionException $e) {
            throw new AuthenticationException('LDAP connection failed: ' . $e->getMessage());
        }
    }
}
