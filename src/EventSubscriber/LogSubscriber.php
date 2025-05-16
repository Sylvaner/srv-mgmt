<?php

/**
 * Ajouter automatiquement des logs dans certains cas.
 */

namespace App\EventSubscriber;

use DateTime;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\App;
use App\Entity\Log;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {
    }

    /**
     * Enregistrement des évènements à différents moments du processus
     * Les suppressions sont gérées avec l'écriture car après, l'enregistrement n'existe plus.
     *
     * @return array<string, array<array<string, int|string>>> Liste des évènements
     */
    public static function getSubscribedEvents(): array
    {
        /** @phpstan-ignore-next-line */
        return [
            KernelEvents::VIEW => [
                ['writeLogDelete', EventPriorities::PRE_WRITE],
                ['writeLogPatch', EventPriorities::POST_WRITE]
            ],
        ];
    }

    /**
     * Capture les évènements de modification qui nécessite d'être journalisée
     *
     * @param $viewEvent Evènement du Kernel
     */
    public function writeLogDelete(ViewEvent $viewEvent): void
    {
        $request = $viewEvent->getRequest();
        $writeLog = false;
        $log = new Log();
        $object = $viewEvent->getControllerResult();
        if (Request::METHOD_DELETE === $request->getMethod()) {
            // Suppression d'une application
            if ($object instanceof App) {
                $log->setMessage('Suppression de ' . $object->getName());
                $log->setServer($object->getServer());
                $writeLog = true;
            }
        }
        if ($writeLog) {
            $log->setDate(new DateTime());
            $log->setUsername($this->security->getUser()->getUserIdentifier());
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }

    /**
     * Capture les évènements de modification qui nécessite d'être journalisée
     *
     * @param $viewEvent Evènement du Kernel
     */
    public function writeLogPatch(ViewEvent $viewEvent): void
    {
        $request = $viewEvent->getRequest();
        $writeLog = false;
        $log = new Log();
        $object = $viewEvent->getControllerResult();
        if (Request::METHOD_PATCH === $request->getMethod()) {
            // Mise à jour d'une application
            if ($object instanceof App && $request->getPayload()->has('currentVersion')) {
                $log->setMessage('Mise à jour de ' . $object->getName() . ' - ' . $object->getCurrentVersion());
                $log->setServer($object->getServer());
                $writeLog = true;
                // Mise à jour d'un serveur
            } elseif ($object instanceof Server && $request->getPayload()->has('lastUpdate')) {
                $log->setMessage('Mise à jour du serveur');
                $log->setServer($object);
                $writeLog = true;
            } elseif ($object instanceof Server && $request->getPayload()->has('lastCheck')) {
                $log->setMessage('Vérification du serveur');
                $log->setServer($object);
                $writeLog = true;
            }
        }
        if ($writeLog) {
            $log->setDate(new DateTime());
            $log->setUsername($this->security->getUser()->getUserIdentifier());
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}
