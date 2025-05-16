<?php

namespace App\Command;

use App\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-password',
    description: 'Generate a hashed password for local user authentication',
)]
class HashPasswordCommand extends Command
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('password', InputArgument::REQUIRED, 'The password to hash')
            ->setHelp(<<<EOT
This command allows you to hash a password for use in the local user configuration.

Example usage:
    <info>php bin/console app:hash-password mySecurePassword</info>

Then you can use the generated hash in your APP_LOCAL_USERS environment variable:
    <info>APP_LOCAL_USERS='{"admin":{"password":"<hashed-password>","roles":["ROLE_ADMIN"]}}'</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $plainPassword = $input->getArgument('password');

        // Create a temporary user to hash the password
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $io->success('Hashed password: ' . $hashedPassword);
        $io->info('Use this value in your APP_LOCAL_USERS environment variable for the password field.');
        $io->info('Example: {"admin":{"password":"' . $hashedPassword . '","roles":["ROLE_ADMIN"]}}');

        return Command::SUCCESS;
    }
}
