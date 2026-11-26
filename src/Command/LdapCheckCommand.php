<?php

namespace App\Command;

use App\Service\LdapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[AsCommand(
    name: 'app:ldap:check',
    description: 'Teste la connexion LDAP et affiche la raison exacte d\'un échec',
)]
class LdapCheckCommand extends Command
{
    public function __construct(
        private LdapService $ldapService,
        #[Autowire('%app.auth_type%')] private string $authType,
        #[Autowire('%env(LDAP_HOST)%')] private string $host,
        #[Autowire('%env(default::LDAP_PORT)%')] private ?string $port,
        #[Autowire('%env(LDAP_ENCRYPTION)%')] private string $encryption,
        #[Autowire('%env(LDAP_USER_KEY)%')] private string $userKey,
        #[Autowire('%env(LDAP_BASE_SEARCH_DN)%')] private string $baseSearch,
        #[Autowire('%env(LDAP_USER_DN)%')] private string $serviceUser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Identifiant à tester')
            ->setHelp(
                "Le mot de passe est demandé de façon masquée : il n'apparaît ni dans l'historique du shell "
                . "ni dans les logs du serveur web.\n\n    <info>php bin/console app:ldap:check s.dangin</info>"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = (string) $input->getArgument('username');

        $io->definitionList(
            ['Type de login (APP_AUTH_TYPE)' => $this->authType],
            ['Serveur' => sprintf('%s:%s (chiffrement : %s)', $this->host, $this->port ?: 'défaut', $this->encryption)],
            ['Compte de service' => $this->serviceUser],
            ['Base de recherche' => $this->baseSearch],
            ['Attribut de login' => $this->userKey],
        );

        if ('ldap' !== $this->authType) {
            $io->warning("APP_AUTH_TYPE vaut '{$this->authType}' : le login de l'application n'utilise pas LDAP.");
        }

        $password = $io->askHidden('Mot de passe');
        if (null === $password || '' === $password) {
            $io->error('Mot de passe vide.');

            return Command::FAILURE;
        }

        try {
            $entry = $this->ldapService->checkCredentials($username, $password);
        } catch (AuthenticationException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Authentification réussie : ' . $entry->getDn());

        return Command::SUCCESS;
    }
}
