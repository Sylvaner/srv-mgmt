# Outil de Gestion des Serveurs (srv-mgmt)

## Présentation du projet

Cet outil de gestion des serveurs permet de :

- Suivre les actions de maintenance réalisées sur les serveurs
- Suivre les mises à jour des applications et des conteneurs Docker
- Être informé visuellement et par email des mises à jour à réaliser

L'application est basée sur une API REST en Symfony avec support pour l'authentification LDAP et utilisateurs locaux.

## Table des matières

- [Installation et configuration](#installation-et-configuration)
  - [Installation sans Docker](#installation-sans-docker)
  - [Installation avec Docker](#installation-avec-docker)
- [Configuration de l'application](#configuration-de-lapplication)
- [Génération du frontend](#génération-du-frontend)
- [Déclaration des applications à surveiller](#déclaration-des-applications-à-surveiller)
- [Développement](#développement)
- [Tests](#tests)

## Installation et configuration

### Installation sans Docker

#### Configuration requise

- PHP 8.3 ou supérieur avec les extensions suivantes :
  - pdo_pgsql (pour PostgreSQL) ou pdo_mysql (pour MySQL / MariaDB)
  - ldap
  - intl
  - xml
  - mbstring
  - zip
- Serveur web (Apache ou Nginx)
- PostgreSQL 15 ou MySQL 8.0
- Composer (gestionnaire de dépendances PHP)
- Node.js et Yarn (pour le frontend)

#### Étapes d'installation

1. Cloner le dépôt :

   ```bash
   git clone https://github.com/Sylvaner/srv-mgmt.git
   cd srv-mgmt
   ```

2. Installer les dépendances via Composer :

   ```bash
   composer install
   ```

3. Configurer les paramètres de connexion à la base de données dans le fichier `.env` :

   ```
   # Configuration PostgreSQL
   POSTGRES_DB=srv_mgmt
   POSTGRES_USER=srv_mgmt_user
   POSTGRES_PASSWORD=your_password
   POSTGRES_PORT=5432
   POSTGRES_VERSION=16
   POSTGRES_HOST=localhost

   # URL de la base de données
   DATABASE_URL="postgresql://srv_mgmt_user:your_password@localhost:5432/srv_mgmt?serverVersion=16&charset=utf8"
   ```

4. Générer la clé publique et privée pour les tokens JWT :

   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

5. Créer la base de données et charger les données initiales :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   php bin/console doctrine:fixtures:load --group=prod
   ```

#### Configuration du serveur web

##### Apache

Créer un virtualhost dans votre configuration Apache :

```apache
<VirtualHost *:80>
    ServerName srv-mgmt.local
    DocumentRoot /var/www/srv-mgmt/public

    <Directory /var/www/srv-mgmt/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/srv-mgmt_error.log
    CustomLog ${APACHE_LOG_DIR}/srv-mgmt_access.log combined
</VirtualHost>
```

##### Nginx

Créer un fichier de configuration dans `/etc/nginx/sites-available/srv-mgmt` :

```nginx
server {
    listen 80;
    server_name srv-mgmt.local;
    root /var/www/srv-mgmt/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    error_log /var/log/nginx/srv-mgmt_error.log;
    access_log /var/log/nginx/srv-mgmt_access.log;
}
```

Activer le site :

```bash
sudo ln -s /etc/nginx/sites-available/srv-mgmt /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Installation avec Docker

#### Prérequis Docker

- Docker Engine 24.0 ou supérieur
- Docker Compose V2 ou supérieur

#### Structure des fichiers Docker

Le projet est configuré avec les services suivants :

- Nginx (serveur web)
- PHP-FPM 8.3 (avec extensions pour PostgreSQL, MySQL et LDAP)
- PostgreSQL 15

Structure des fichiers Docker :

```
srv-mgmt/
├── docker-compose.yml
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php-fpm/
│       ├── Dockerfile
│       └── php.ini
```

#### Étapes d'installation avec Docker

1. Cloner le dépôt :

   ```bash
   git clone https://github.com/Sylvaner/srv-mgmt.git
   cd srv-mgmt
   ```

2. Configurer le fichier `.env` avec les paramètres Docker :

   ```
   # URL de la base de données pour Docker
   DATABASE_URL="postgresql://postgres:postgres@database:5432/srv_mgmt?serverVersion=15&charset=utf8"
   ```

3. Lancer les conteneurs :

   ```bash
   docker-compose up -d
   ```

4. Installer les dépendances et configurer l'application :
   ```bash
   docker-compose exec php-fpm composer install
   docker-compose exec php-fpm php bin/console lexik:jwt:generate-keypair
   docker-compose exec php-fpm php bin/console doctrine:schema:create
   docker-compose exec php-fpm php bin/console doctrine:fixtures:load --group=prod
   ```

## Configuration de l'application

### Authentification

L'application supporte deux modes d'authentification :

1. **LDAP** : Authentification via un serveur LDAP externe
2. **Local** : Authentification via une liste d'utilisateurs définie dans la configuration

La configuration du mode d'authentification se fait dans le fichier `.env` :

```
# Mode d'authentification ('ldap' ou 'local')
APP_AUTH_TYPE=ldap

# Fournisseur d'authentification ('ldap_server' ou 'local_users')
APP_AUTH_PROVIDER=ldap_server
```

Ainsi que dans le fichier `config/packages/security.yaml`, les paramètres providers :

```
  firewalls:
    dev:
      pattern: ^/(_(profiler|wdt)|css|images|js)/
      security: false
    refresh:
      pattern: ^/api/token/refresh
      stateless: true
      provider: ldap_server
      refresh_jwt: ~
    main:
      lazy: true
      provider: ldap_server
      stateless: true
      jwt: ~
```

#### Configuration LDAP

Si vous utilisez le mode LDAP, configurez les paramètres de connexion au serveur LDAP :

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

Si vous utilisez l'authentification locale, définissez les utilisateurs et leurs mots de passe :

```
APP_LOCAL_USERS='{"admin":{"password":"$2y$13$iAzB6GjvptRzdF6.JujM1OsgIOGWZzq1bNyBMP.HitKndUdVsM4Em","roles":["ROLE_ADMIN"]},"user":{"password":"$2y$13$KE.i/7D0jJgAqfM1/FygmeJ2HLSw6b7TrSJAVC9VAgNZDjRPUEwa6","roles":["ROLE_ADMIN"]}}'
```

Informations de connexion :

- admin : admin_password
- user : user_password

## Génération du frontend

### Sans Docker

Pour générer le frontend, exécutez ces commandes dans le répertoire `front-src` :

```bash
# Installer les dépendances
yarn install

# Générer le frontend
yarn build

# Les fichiers générés seront dans le dossier public/
```

### Avec Docker

Vous pouvez utiliser un conteneur temporaire pour générer le frontend :

```bash
docker run -it --rm -v $(pwd):/app node:22 bash
cd /app/front-src
yarn install
yarn build
```

## Application

### Configuration générale

Dans l'onglet "Général", vous pouvez configurer les paramètres suivants :

- Seuil d'alerte : Nombre de jours à partir duquel le serveur affiche un problème
- Seuil d'avertissement : Nombre de jours à partir duquel le serveur affiche un avertissement

### Déclaration des serveurs

Dans l'onglet "Serveurs", vous pouvez ajouter des serveurs à surveiller.
L'adresse IP n'a pas d'impacte sur le fonctionnement de l'outil. La documentation permet de créer un lien vers une documentation liée au serveur.

### Déclaration des applications à surveiller

L'outil permet de suivre les mises à jour de différents types d'applications. Le champ documentation permet de créer un lien vers une page web avec des informations sur la mise à jour.

### Types d'applications supportés

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

## Développement

### Environnement de développement avec Docker

```bash
cd dev
docker compose --env-file .env.local up -d
docker exec -it mgmt-apache bash
cd /var/www/html
composer install
```

Générer la clé publique et privée pour les tokens JWT :

```bash
php bin/console lexik:jwt:generate-keypair
```

Créer la base de données :

```bash
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --group=dev
```

Pour effacer les données de l'ancienne base de données :

```bash
php bin/console doctrine:schema:drop --force
```

L'utilisateur du faux LDAP est `adminuser1` et a pour mot de passe `password`.

### Configuration du frontend pour le développement

Pour le développement du back office, configurez le frontend :
Dans le fichier `front-src/quasar.config.ts`, dans la section build -> env, remplacez la valeur de `baseUrl` par `http://localhost`.

## Tests

### Lancement de l'environnement de test

```bash
docker compose --env-file .env.test.local -f tests/docker-compose.yaml up -d
```

### Préparation de la base de données de test

```bash
docker exec -it rest-test-php bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --purge-with-truncate --group=tests --env=test
```

### Exécution des tests

```bash
docker exec -it rest-test-php bash
cd /app
php bin/phpunit --coverage-text
```

## Dépannage

### Problèmes courants

#### Problèmes de permissions

```bash
# Sans Docker
sudo chown -R www-data:www-data var/

# Avec Docker
docker-compose exec php-fpm chown -R www-data:www-data var/
```

#### Vider le cache

```bash
# Sans Docker
php bin/console cache:clear

# Avec Docker
docker-compose exec php-fpm php bin/console cache:clear
```

#### Accès aux logs

```bash
# Sans Docker
tail -f var/log/dev.log

# Avec Docker
docker-compose exec php-fpm tail -f var/log/dev.log
```
