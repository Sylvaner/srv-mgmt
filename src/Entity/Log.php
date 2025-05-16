<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\LogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ApiResource(
    description: 'Journaux',
    order: ['date' => 'DESC'],
    normalizationContext: [ 'groups' => ['logs'] ]
)]
#[ApiResource(
    uriTemplate: '/servers/{id}/logs',
    uriVariables: [
        'id' => new Link(fromClass: Log::class, toProperty: 'server'),
    ],
    normalizationContext: [ 'groups' => ['logs'] ],
    operations: [
        new GetCollection()
    ],
    order: ['id' => 'DESC']
)]
class Log
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['logs'])]
    // @phpstan-ignore-next-line
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['logs'])]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['logs'])]
    private ?Server $server = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['logs'])]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    #[Groups(['logs'])]
    private ?string $username = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }
}
