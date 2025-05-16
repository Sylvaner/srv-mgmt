<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;

class BaseApiTestCase extends ApiTestCase
{
    protected $entityManager = null;
    private static ?string $token = null;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    private function getToken(): string {
        if (static::$token) {
            return static::$token;
        }
        // Obtenir le token
        $authUsername = 'adminuser1';
        $authPassword = 'password';
        
        // If we're using local auth in test environment, use the test user from .env.test
        if ($_ENV['APP_AUTH_TYPE'] === 'local') {
            $authUsername = 'testadmin';
            $authPassword = 'testpassword';
        }
        
        $response = static::createClient()->request(
            'POST',
            '/api/login',
            [
                'json' => [
                    'username' => $authUsername,
                    'password' => $authPassword
                ]
            ]
        );
        static::$token = $response->toArray()['token'];
        return static::$token;
    }

    protected function createClientWithCredentials(): Client
    {
        return static::createClient([], ['headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'authorization' => 'Bearer ' . $this->getToken()
        ]]);
    }
}
