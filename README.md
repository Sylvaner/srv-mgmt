# Outil de Gestion des Serveurs (srv-mgmt)

Version: 1.0.0 (hash: 7b3e2c9)

## Présentation du projet

Cet outil de gestion des serveurs permet de :

- Suivre les actions de maintenance réalisées sur les serveurs
- Suivre les mises à jour des applications et des conteneurs Docker
- Être informé visuellement et par email des mises à jour à réaliser

L'application est basée sur une API REST en Symfony avec support pour l'authentification LDAP et utilisateurs locaux.

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL/MariaDB
- Node.js et Yarn pour le frontend

### Étapes d'installation

Générer la clé publique et privée pour les tokens JWT :

```
php bin/console lexik:jwt:generate-keypair
```

Créer la base de données :

```
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --group=prod
```

Construction du frontend :

```
cd front-src
yarn
yarn build
```

## Configuration

### Authentification

L'application supporte deux modes d'authentification :

1. **LDAP** : Authentification via un serveur LDAP externe
2. **Local** : Authentification via une liste d'utilisateurs définie dans la configuration

La configuration du mode d'authentification se fait dans le fichier `.env` avec les variables suivantes :

```
# Mode d'authentification ('ldap' ou 'local')
APP_AUTH_TYPE=ldap

# Fournisseur d'authentification ('ldap_server' ou 'local_users')
APP_AUTH_PROVIDER=ldap_server
```

#### Configuration LDAP

Si vous utilisez le mode LDAP, vous devez configurer les paramètres de connexion au serveur LDAP :

```
LDAP_HOST=127.0.0.1
LDAP_PORT=389
LDAP_ENCRYPTION=none
LDAP_VERSION=3
LDAP_USER_KEY=uid
LDAP_BASE_DN="dc=example,dc=com"
LDAP_BASE_SEARCH_DN="ou=people,dc=example,dc=com"
LDAP_USER_DN="cn=admin,dc=example,dc=com"
LDAP_USER_PASSWORD="admin_password"
```

#### Configuration des utilisateurs locaux

Si vous utilisez le mode d'authentification local, vous devez définir les utilisateurs et leurs mots de passe dans la variable `APP_LOCAL_USERS` au format JSON :

```
APP_LOCAL_USERS='{"admin":{"password":"$2y$13$iAzB6GjvptRzdF6.JujM1OsgIOGWZzq1bNyBMP.HitKndUdVsM4Em","roles":["ROLE_ADMIN"]},"user":{"password":"$2y$13$KE.i/7D0jJgAqfM1/FygmeJ2HLSw6b7TrSJAVC9VAgNZDjRPUEwa6","roles":["ROLE_ADMIN"]}}'
```

- admin : admin_password
- user : user_password

L'ensemble des autres informations de configuration se trouvent également dans le fichier `.env`

### Déclaration des applications

L'outil permet de suivre les mises à jour de différents types d'applications. Le champ documentation peremt de créer un lien vers une page web avec des informations sur la mise à jour de l'application.
Voici comment configurer chaque type :

#### Type Debian

L'information est recherchée sur les dépôts Debian.

**Exemple :**

```
Nom : Sympa
Information de mise à jour : sympa
```

#### Type GitHub Release

Les releases du dépôt GitHub sont recherchées.

**Exemple :**

```
Nom : Nextcloud
Information de mise à jour : nextcloud/server
```

#### Type GitHub Tag

Les tags du dépôt GitHub sont recherchés.

**Exemple :**

```
Nom : Limesurvey
Information de mise à jour : LimeSurvey/LimeSurvey
```

#### Type Docker

L'information est récupérée sur le Docker Registry.

**Exemple :**

```
Nom : Guacamole
Information de mise à jour : guacamole/guacamole
```

#### Type Crawler

L'information est recherchée sur une page web avec un querySelector JavaScript.

**Exemple :**

```
Nom : WAPT
Information de mise à jour : https://www.wapt.fr/en/doc/wapt-changelog.html
Information complémentaire : #changelog section h3
```

## Développement (Docker)

```
cd dev
docker compose --env-file .env.local up -d
docker exec -it mgmt-apache bash
cd /var/www/html
```

Générer la clé publique et privée pour les tokens JWT :

```
php bin/console lexik:jwt:generate-keypair
```

Créer la base de données :

```
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --group=dev
```

Pour effacer les données de l'ancienne base de données :

```
php bin/console doctrine:schema:drop --force
```

L'utilisateur du faux LDAP est adminuser1 et a pour mot de passe password.

Lors du développement back office, il est possible de construire le frontend pour le développement.
Dans le fichier `front-src/quasar.config.ts`, dans la section build -> env, remplacez la valeur de baseUrl par http://localhost.

## Tests

Lancement de l'environnement :

```
docker compose --env-file .env.test.local -f tests/docker-compose.yaml up -d
```

Puis créer la base de données :

```
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --purge-with-truncate --group=tests --env=test
```

Se connecter au docker :

```
docker exec -it rest-test-php bash
cd /app
php bin/phpunit --coverage-text
```
