<?php

namespace App\Tests\Controller;

use App\Entity\RefreshToken;
use App\Entity\User;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class LoginControllerTest extends WebTestCase
{
    private $entityManager = null;
    private $client = null;
    private $parameterBag = null;
    private $originalAuthType = null;
    private $originalAuthProvider = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        
        // Save original auth configuration
        $this->originalAuthType = $_ENV['APP_AUTH_TYPE'] ?? 'local';
        $this->originalAuthProvider = $_ENV['APP_AUTH_PROVIDER'] ?? 'local_users';

        // Nettoyer les tokens de rafraîchissement existants
        $refreshTokenRepository = $this->entityManager->getRepository(RefreshToken::class);
        $refreshTokens = $refreshTokenRepository->findAll();
        foreach ($refreshTokens as $token) {
            $this->entityManager->remove($token);
        }
        $this->entityManager->flush();
    }
    
    protected function tearDown(): void
    {
        // Restore original auth configuration
        $_ENV['APP_AUTH_TYPE'] = $this->originalAuthType;
        $_ENV['APP_AUTH_PROVIDER'] = $this->originalAuthProvider;
        
        parent::tearDown();
    }

    public function testBadContent()
    {
        $this->client->request('POST', '/api/login', [], [], [], '{"username":"user","password":"password"}');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testEmptyContent()
    {
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], '');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testMalformedData()
    {
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], '{"username":"user"}');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        $this->client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], '{"password":"data"}');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testLdapLogin()
    {
        // Set auth type to LDAP for this test
        $_ENV['APP_AUTH_TYPE'] = 'ldap';
        $_ENV['APP_AUTH_PROVIDER'] = 'ldap_server';
        
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"username":"adminuser1","password":"password"}'
        );
        
        // Test de la réponse
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_validate($response->getContent()));

        // Test du token
        $container = static::getContainer();
        $tokenManager = $container->get(JWTTokenManagerInterface::class);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('adminuser1', $tokenManager->parse($data['token'])['username']);

        $refreshTokenManager = $container->get(RefreshTokenManagerInterface::class);
        $this->assertEquals('adminuser1', $refreshTokenManager->get($data['refresh_token'])->getUsername());

        // Test de la base de données
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(1, count($allUsers));
        $userFound = false;
        foreach ($allUsers as $user) {
            if ($user->getLogin() === 'adminuser1') {
                $userFound = true;
                $this->assertContains('ROLE_USER', $user->getRoles());
                break;
            }
        }
        $this->assertTrue($userFound, 'User adminuser1 not found in database');

        $allRefreshTokens = $this->entityManager->getRepository(RefreshToken::class)->findAll();
        $this->assertCount(1, $allRefreshTokens);
        $this->assertEquals('adminuser1', $allRefreshTokens[0]->getUsername());
        $this->assertEquals($data['refresh_token'], $allRefreshTokens[0]->getRefreshToken());
    }
    
    public function testLocalLogin()
    {
        // Set auth type to local for this test
        $_ENV['APP_AUTH_TYPE'] = 'local';
        $_ENV['APP_AUTH_PROVIDER'] = 'local_users';
        
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"username":"testuser","password":"testpassword"}'
        );
        
        // Test de la réponse
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue(json_validate($response->getContent()));

        // Test du token
        $container = static::getContainer();
        $tokenManager = $container->get(JWTTokenManagerInterface::class);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals('testuser', $tokenManager->parse($data['token'])['username']);

        $refreshTokenManager = $container->get(RefreshTokenManagerInterface::class);
        $this->assertEquals('testuser', $refreshTokenManager->get($data['refresh_token'])->getUsername());

        // Test de la base de données
        $allUsers = $this->entityManager->getRepository(User::class)->findAll();
        $userFound = false;
        foreach ($allUsers as $user) {
            if ($user->getLogin() === 'testuser') {
                $userFound = true;
                $this->assertContains('ROLE_USER', $user->getRoles());
                break;
            }
        }
        $this->assertTrue($userFound, 'User testuser not found in database');

        $allRefreshTokens = $this->entityManager->getRepository(RefreshToken::class)->findAll();
        $this->assertGreaterThanOrEqual(1, count($allRefreshTokens));
        $tokenFound = false;
        foreach ($allRefreshTokens as $token) {
            if ($token->getUsername() === 'testuser') {
                $tokenFound = true;
                break;
            }
        }
        $this->assertTrue($tokenFound, 'Refresh token for testuser not found');
    }
}
