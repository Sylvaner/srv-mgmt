<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\LdapService;
use App\Service\LocalAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class LoginController extends AbstractController
{
    private string $authType;

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger
    ) {
        $this->authType = $this->parameterBag->get('app.auth_type') ?? 'ldap';
    }

    /**
     * Connexion de l'utilisateur pour obtenir son token
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function loginCheck(
        Request $request,
        LdapService $ldapService,
        LocalAuthService $localAuthService,
        AuthenticationSuccessHandler $authenticationSuccessHandler,
        AuthenticationFailureHandler $authenticationFailureHandler
    ): Response {
        $content = json_decode($request->getContent());
        $response = new JsonResponse();
        try {
            if ($content === null || !property_exists($content, 'username') || !property_exists($content, 'password')) {
                throw new AuthenticationException('Invalid credentials data');
            }

            $authSuccess = false;

            // Choose auth method based on configuration
            if ($this->authType === 'ldap') {
                // Lève une AuthenticationException si l'authentification échoue
                $ldapService->checkCredentials($content->username, $content->password);
                $authSuccess = true;
            } elseif ($this->authType === 'local') {
                $authSuccess = $localAuthService->checkCredentials($content->username, $content->password);
            } else {
                throw new AuthenticationException('Invalid authentication type configured');
            }

            if (!$authSuccess) {
                throw new AuthenticationException('Authentication failed');
            }

            $userFromRepo = $this->userRepository->findOneBy(['login' => $content->username]);

            // Création de l'utilisateur s'il n'existe pas
            if (null == $userFromRepo) {
                $userFromRepo = new User();
                $userFromRepo->setLogin($content->username);
                try {
                    $userFromRepo->setRoles(['ROLE_USER']);
                    $userFromRepo->setLastLogin(new \DateTime());
                    $userFromRepo->setActive(true);
                } catch (\InvalidArgumentException) {
                    throw new AuthenticationException('User registration issue.');
                }
            } else {
                // Met à jour l'information de dernière connexion
                $userFromRepo->setLastLogin(new \DateTime());
            }

            $this->entityManager->persist($userFromRepo);
            $this->entityManager->flush();
            return $authenticationSuccessHandler->handleAuthenticationSuccess($userFromRepo);
        } catch (AuthenticationException $e) {
            // Le client ne reçoit qu'un 401 générique : la raison réelle est tracée ici (jamais le mot de passe)
            $this->logger->warning('Login failed for "{username}": {reason}', [
                'username' => is_object($content) && is_string($content->username ?? null) ? $content->username : '',
                'reason' => $e->getMessage(),
            ]);
            return $authenticationFailureHandler->onAuthenticationFailure($request, $e);
        }
    }
}
