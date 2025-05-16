<?php

namespace App\Tests\Service;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenTest extends WebTestCase
{
    private $token = '';
    private $refreshToken = '';
    private $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Determine which user credentials to use based on authentication type
        $authUsername = 'adminuser1';
        $authPassword = 'password';
        
        // If using local auth, use the test credentials instead
        if (isset($_ENV['APP_AUTH_TYPE']) && $_ENV['APP_AUTH_TYPE'] === 'local') {
            $authUsername = 'testadmin';
            $authPassword = 'testpassword';
        }
        
        // Génération d'un token valide
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $authUsername,
                'password' => $authPassword
            ])
        );
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->token = $data['token'];
        $this->refreshToken = $data['refresh_token'];
        // Sinon les mêmes token sont regénérés
        sleep(1);
    }

    public function testRefreshWithoutRefreshToken()
    {
        $this->client->request(
            'POST',
            '/api/token/refresh',
            [],
            [],
            [],
            '{"refresh_token": ""}'
        );
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testRefreshWithRefreshToken()
    {
        $this->client->request('POST', '/api/token/refresh', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], '{"refresh_token": "' . $this->refreshToken . '"}');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $container = static::getContainer();
        $tokenManager = $container->get(JWTTokenManagerInterface::class);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        // Verify the username matches what we expect based on auth type
        $expectedUsername = isset($_ENV['APP_AUTH_TYPE']) && $_ENV['APP_AUTH_TYPE'] === 'local' ? 'testadmin' : 'adminuser1';
        $this->assertEquals($expectedUsername, $tokenManager->parse($data['token'])['username']);
        $this->assertNotEquals($this->token, $data['token']);

        $refreshTokenManager = $container->get(RefreshTokenManagerInterface::class);
        // Verify the refresh token username matches what we expect based on auth type
        $expectedUsername = isset($_ENV['APP_AUTH_TYPE']) && $_ENV['APP_AUTH_TYPE'] === 'local' ? 'testadmin' : 'adminuser1';
        $this->assertEquals($expectedUsername, $refreshTokenManager->get($data['refresh_token'])->getUsername());
        $this->assertEquals($this->refreshToken, $data['refresh_token']);
    }
}
