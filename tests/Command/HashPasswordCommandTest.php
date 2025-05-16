<?php

namespace App\Tests\Command;

use App\Command\HashPasswordCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class HashPasswordCommandTest extends KernelTestCase
{
    private ?CommandTester $commandTester = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        
        $application = new Application(self::$kernel);
        $command = new HashPasswordCommand($passwordHasher);
        $application->add($command);
        
        $this->commandTester = new CommandTester($command);
    }

    public function testHashPassword(): void
    {
        $this->commandTester->execute([
            'password' => 'test-password',
        ]);

        $output = $this->commandTester->getDisplay();
        
        // Assert that command completed successfully
        $this->assertEquals(0, $this->commandTester->getStatusCode());
        // Assert that the output contains a hashed password (contains a $ sign)
        $this->assertMatchesRegularExpression('/\$2y\$/', $output);
        // Assert the output contains usage instructions
        $this->assertStringContainsString('Use this value in your APP_LOCAL_USERS environment variable', $output);
    }
}
