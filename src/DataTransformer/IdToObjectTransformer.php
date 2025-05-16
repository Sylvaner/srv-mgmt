<?php

/**
 * Classe permettant la transformation des ID en lien IRI
 * Ce lien permet à API Platform de gérer les liens
 */

namespace App\DataTransformer;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Entity\App;
use App\Entity\AppUpdateType;
use App\Entity\Server;
use App\Entity\ServerType;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class IdToObjectTransformer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private IriConverterInterface $iriConverter;

    public function __construct(IriConverterInterface $iriConverter)
    {
        $this->iriConverter = $iriConverter;
    }

    /**
     * Si une classe est reconnue, converti l'identifiant en IRI
     */
    /**
     * Convertit les ID en liens IRI
     *
     * @param array<string, mixed> $data Données à convertir
     * @param string $class Classe cible
     * @param string|null $format Format
     * @param array<string, mixed> $context Contexte
     *
     * @return mixed Données converties
     */
    public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): mixed
    {
        // Data is guaranteed to be an array by PHPDoc
        switch ($class) {
            case App::class:
                if (isset($data['updateType'])) {
                    $data['updateType'] = $this->iriConverter->getIriFromResource(
                        resource: AppUpdateType::class,
                        context: ['uri_variables' => ['id' => $data['updateType']]]
                    );
                }
                if (isset($data['server'])) {
                    $data['server'] = $this->iriConverter->getIriFromResource(
                        resource: Server::class,
                        context: ['uri_variables' => ['id' => $data['server']]]
                    );
                }
                break;
            case Server::class:
                $data['type'] = $this->iriConverter->getIriFromResource(
                    resource: ServerType::class,
                    context: ['uri_variables' => ['id' => $data['type']]]
                );
                break;
        }
        return $this->denormalizer->denormalize($data, $class, $format, $context + [__CLASS__ => true]);
    }

    /**
     * Liste des cas acceptés
     */
    /**
     * Vérifie si la dénormalisation est supportée
     *
     * @param array<string, mixed> $data Données à vérifier
     * @param string $type Type cible
     * @param string|null $format Format
     * @param array<string, mixed> $context Contexte
     *
     * @return bool True si supporté
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        // Data is guaranteed to be an array by PHPDoc
        return in_array($format, ['json'], true) &&
            (
                (is_a($type, Server::class, true) && !empty($data['type'])) ||
                (is_a($type, App::class, true) && !empty($data['updateType']))
            ) &&
            !isset($context[__CLASS__]);
    }

    /**
     * Liste des types acceptés
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => null,
            '*' => false,
            Server::class => true,
            App::class => true
        ];
    }
}
