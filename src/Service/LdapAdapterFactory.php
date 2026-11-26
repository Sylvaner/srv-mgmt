<?php

namespace App\Service;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;

/**
 * Construit l'adapter LDAP en gérant le chiffrement des échanges.
 *
 * - encryption = none : connexion en clair (port 389), à réserver au développement
 * - encryption = ssl  : LDAPS (port 636)
 * - encryption = tls  : StartTLS sur le port 389
 */
final class LdapAdapterFactory
{
    /**
     * Valeurs acceptées pour la vérification du certificat du serveur LDAP.
     * Correspond à la directive TLS_REQCERT d'OpenLDAP.
     */
    private const REQUIRE_CERT = [
        'never' => LDAP_OPT_X_TLS_NEVER,
        'allow' => LDAP_OPT_X_TLS_ALLOW,
        'try' => LDAP_OPT_X_TLS_TRY,
        'demand' => LDAP_OPT_X_TLS_DEMAND,
        'hard' => LDAP_OPT_X_TLS_HARD,
    ];

    public static function create(
        string $host,
        int $port,
        string $encryption,
        int $version,
        ?string $caCertFile = null,
        ?string $caCertDir = null,
        string $requireCert = 'demand'
    ): Adapter {
        $encryption = strtolower($encryption);
        $options = [
            'protocol_version' => $version,
            'referrals' => false,
        ];

        if ('none' !== $encryption) {
            $requireCert = strtolower($requireCert) ?: 'demand';
            if (!isset(self::REQUIRE_CERT[$requireCert])) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid LDAP_TLS_REQUIRE_CERT "%s", expected one of: %s.',
                    $requireCert,
                    implode(', ', array_keys(self::REQUIRE_CERT))
                ));
            }
            // Par défaut, le certificat du serveur est vérifié avec les autorités du système
            $options['x_tls_require_cert'] = self::REQUIRE_CERT[$requireCert];
            // Autorité de certification propre à l'organisation (certificat auto-signé, PKI interne)
            if (null !== $caCertFile && '' !== $caCertFile) {
                $options['x_tls_cacertfile'] = $caCertFile;
            }
            if (null !== $caCertDir && '' !== $caCertDir) {
                $options['x_tls_cacertdir'] = $caCertDir;
            }
        }

        return new Adapter([
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'version' => $version,
            'options' => $options,
        ]);
    }
}
