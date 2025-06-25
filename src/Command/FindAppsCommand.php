<?php

/**
 * Commande pour la recherche de mises à jour des applications
 */

namespace App\Command;

use App\Repository\AppRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:check-updates')]
class FindAppsCommand extends Command
{
    /** @var array<string, mixed> Cache des requêtes */
    private array $cache = [];
    private bool $debug = false;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AppRepository $appRepository,
        private HttpClientInterface $client,
        private MailerInterface $mailer,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition(new InputDefinition(
            [
                new InputOption('debug', null, null, 'Informations de debuggage.')
            ]
        ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->debug = $input->getOption('debug');
        $sendEmail = false;
        $mailContent = '<p>Applications à mettre à jour : </p><ul>';
        // Recherche les mises à jour pour chaque application
        foreach ($this->appRepository->findAll() as $app) {
            if ($this->debug) {
                $output->writeln($app->getName() . ' - ' . $app->getUpdateType()->getName());
            }
            if (!$app->getServer()->isEnabled()) {
                continue;
            }
            $updateType = $app->getUpdateType()->getName();
            $updateResource = $app->getUpdateResource();
            $latestVersion = null;
            // Cache pour éviter de rechercher plusieurs fois l'information
            // pour une même application
            if (array_key_exists($updateType . $updateResource, $this->cache)) {
                $latestVersion = $this->cache[$updateType . $updateResource];
                if ($this->debug) {
                    $output->writeln('<info>From cache : ' . $latestVersion . '</info>');
                }
            } else {
                switch ($app->getUpdateType()->getName()) {
                    case 'GitHub Release':
                        $latestVersion = $this->checkGitHubRelease($app->getUpdateResource(), $output);
                        break;
                    case 'GitHub Tag':
                        $latestVersion = $this->checkGitHubTag($app->getUpdateResource(), $output);
                        break;
                    case 'Debian':
                        $latestVersion = $this->checkDebian($app->getUpdateResource(), $output);
                        break;
                    case 'Docker':
                        $latestVersion = $this->checkDocker($app->getUpdateResource(), $output);
                        break;
                    case 'Crawler':
                        $latestVersion = $this->checkCrawler(
                            $app->getUpdateResource(),
                            $app->getExtraUpdateResource(),
                            $output
                        );
                        break;
                }
                if ($this->debug) {
                    $output->writeln('<info> - Found : ' . $latestVersion . '</info>');
                    $output->writeln('<info> - Current : ' . $app->getCurrentVersion() . '</info>');
                    $output->writeln('<info> - Latest : ' . $app->getLatestVersion() . '</info>');
                }
                $this->cache[$updateType . $updateResource] = $latestVersion;
            }
            if (
                 (null !== $latestVersion) &&
                 (
                     ($app->getLatestVersion() !== $latestVersion)
                     ||
                     ($app->getCurrentVersion() !== $latestVersion)
                 )
            ) {
                $mailContent .= '<li>Serveur ' .
                    $app->getServer()->getName() .
                    ' : ' .
                    $app->getName() .
                    ' -> ' .
                    $app->getCurrentVersion() .
                    ' (' .
                    $latestVersion .
                    ')';
                $sendEmail = true;
                $app->setLatestVersion($latestVersion);
                $this->entityManager->persist($app);
            }
        }
        $this->entityManager->flush();
        if ($sendEmail) {
            $mailContent .= '</ul>';
            $email = (new Email())
                ->from($this->params->get('mailer_from'))
                ->to($this->params->get('mailer_rcpt'))
                ->subject('Mise à jour des serveurs')
                ->html($mailContent);
            if ($this->debug) {
                $output->writeln(
                    '<info>Send email from ' .
                    $this->params->get('mailer_from') .
                    ' to ' .
                    $this->params->get('mailer_rcpt') .
                    '</info>'
                );
                $output->writeln('<info>' . $mailContent . '</info>');
            }
            $this->mailer->send($email);
        }
        return Command::SUCCESS;
    }

    /**
     * Se connecte à l'API GitHub et extrait la version de la dernière release
     *
     * @param string $repoId Chemin du dépôt GitHub
     * @param OutputInterface $output Ecriture des informations du mode debug
     *
     * @return string Version du paquet
     */
    private function checkGitHubRelease(string $repoId, OutputInterface $output): ?string
    {
        $checkUrl = "https://api.github.com/repos/$repoId/releases/latest";
        if ($this->debug) {
            $output->writeln($checkUrl);
        }
        try {
            $response = $this->client->request('GET', $checkUrl);
            if (Response::HTTP_OK === $response->getStatusCode()) {
                if (
                    count($response->getHeaders()['content-type']) > 0 &&
                    str_contains($response->getHeaders()['content-type'][0], 'application/json')
                ) {
                        $data = $response->toArray();
                        return $data['tag_name'];
                }
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return null;
    }

    /**
     * Se connecte à l'API GitHub et extrait la version de la dernière des tags
     *
     * @param string $repoId Chemin du dépôt GitHub
     * @param OutputInterface $output Ecriture des informations du mode debug
     *
     * @return string Version du paquet
     */
    private function checkGitHubTag(string $repoId, OutputInterface $output): ?string
    {
        $checkUrl = "https://api.github.com/repos/$repoId/tags";
        if ($this->debug) {
            $output->writeln($checkUrl);
        }
        try {
            $response = $this->client->request('GET', $checkUrl);
            if (Response::HTTP_OK === $response->getStatusCode()) {
                if (
                    count($response->getHeaders()['content-type']) > 0 &&
                    str_contains($response->getHeaders()['content-type'][0], 'application/json')
                ) {
                    $data = $response->toArray();
                    // array_slice works with 10 or minus items
                    foreach (array_slice($data, 0, 10) as $item) {
                        // Check if the name start with a digit
                        if (preg_match('/^v?\d/', $item['name']) && $this->isValidRelease($item['name'])) {
                            return $item['name'];
                        }
                    }
                    // Return first if tag with version not found
                    if (count($data) > 0) {
                        return $data[0]['name'];
                    }
                }
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return null;
    }

    /**
     * Se connecte au site de suivi des paquets de Debian, nettoie les sources,
     * puis extrait la version.
     *
     * @param string $packageName Nom du paquet Debian
     * @param OutputInterface $output Ecriture des informations du mode debug
     *
     * @return ?string Version du paquet ou null en cas d'échec
     */
    private function checkDebian(string $packageName, OutputInterface $output): ?string
    {
        $checkUrl = "https://tracker.debian.org/pkg/$packageName";
        if ($this->debug) {
            $output->writeln($checkUrl);
        }
        $response = $this->client->request('GET', $checkUrl);
        if (Response::HTTP_OK === $response->getStatusCode()) {
            try {
                $content = str_replace(["\r", "\n"], '', strip_tags($response->getContent()));
                if (preg_match('/\s+stable:\s+(.*?)\s/', $content, $matches)) {
                    // @phpstan-ignore-next-line
                    if (count($matches) === 2) {
                        return $matches[1];
                    }
                }
            } catch (Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
        return null;
    }

    /**
     * Se connecte à l'API Docker et extrait le hash de la dernière version latest
     *
     * @param string $repoId Chemin du dépôt Docker
     * @param OutputInterface $output Ecriture des informations du mode debug
     *
     * @return string Hash de la dernière version
     */
    private function checkDocker(string $repoId, OutputInterface $output): ?string
    {
        $checkUrl = "https://hub.docker.com/v2/repositories/$repoId/tags?page_size=50";
        if ($this->debug) {
            $output->writeln($checkUrl);
        }
        try {
            $response = $this->client->request('GET', $checkUrl);
            if (Response::HTTP_OK === $response->getStatusCode()) {
                if (
                    count($response->getHeaders()['content-type']) > 0 &&
                    str_contains($response->getHeaders()['content-type'][0], 'application/json')
                ) {
                    $data = $response->toArray();
                    if (isset($data['results']) && count($data['results']) > 0) {
                        // Parcours de tous les résultats car il peut y avoir beaucoup d'images
                        foreach ($data['results'] as $tag) {
                            // Tentative de filtre des tags
                            $isValidTag = $this->isValidDockerTag($tag);
                            if ($isValidTag) {
                                foreach ($tag['images'] as $image) {
                                    if ($image['architecture'] === 'amd64') {
                                        if ($this->debug) {
                                            $output->writeln($tag['name']);
                                        }
                                        // Seul le début du hash est conservé
                                        return substr(str_replace('sha256:', '', $image['digest']), 0, 12);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return null;
    }

    /**
     * Filtre des tags considérés comme invalides (testing, etc).
     *
     * @param array $tag Information du tag
     *
     * @return boolean True si le tag est valide
     */
    /**
     * Vérifie si un tag Docker est valide
     *
     * @param array<string, mixed> $tag Information du tag
     *
     * @return bool True si le tag est valide
     */
    private function isValidDockerTag(array $tag): bool
    {
        if (!isset($tag['name'])) {
            return false;
        }
        if (!isset($tag['images'])) {
            return false;
        }
        $tagName = $tag['name'];
        return $this->isValidRelease($tagName);
    }

    /**
     * Valider le nom de la release
     *
     * @param string $tagName Nom de la release
     *
     * @return boolean True si le nom de la release est valide
     */
    private function isValidRelease(string $tagName): bool
    {
        if (str_contains($tagName, 'test') || str_contains($tagName, 'beta') || str_contains($tagName, 'alpha')) {
            return false;
        }
        if (preg_match('/RC[1-9]/i', $tagName)) {
            return false;
        }
        return true;
    }

    /**
     * Se connecte au site puis extrait l'information contenu dans une balise
     * indiquée au format CSS.
     *
     * @param string $url Page internet contenant l'information
     * @param string $css Chemin au format CSS de la balise contenant l'information
     * @param OutputInterface $output Ecriture des informations du mode debug
     *
     * @return ?string Version du paquet ou null en cas d'échec
     */
    /**
     * Se connecte au site puis extrait l'information contenu dans une balise
     * indiquée au format CSS.
     *
     * @param string $url URL de la page internet contenant l'information
     * @param string $cssPath Chemin au format CSS de la balise contenant l'information
     * @param OutputInterface $output Interface de sortie pour les messages
     *
     * @return string|null Version du paquet ou null en cas d'échec
     */
    private function checkCrawler(string $url, string $cssPath, OutputInterface $output): ?string
    {
        if ($this->debug) {
            $output->writeln($url);
            $output->writeln($cssPath);
        }
        try {
            $response = $this->client->request('GET', $url);
            if (Response::HTTP_OK === $response->getStatusCode()) {
                $crawler = new Crawler($response->getContent());
                $versionData = $crawler->filter($cssPath)->first()->text();
                if ($this->debug) {
                    $output->writeln($versionData);
                }
                $versionData = str_replace('Version', '', $versionData);
                $versionData = str_replace('version', '', $versionData);
                return trim(preg_replace('/[^a-zA-Z0-9\s\-_().,]/', '', $versionData));
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Bad HTML path</error>');
            return "FAIL";
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
        return null;
    }
}
