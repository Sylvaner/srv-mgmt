<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AppRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AppRepository::class)]
#[ApiResource(
    description: 'Application',
    normalizationContext: [ 'groups' => ['server:read'] ]
)]
class App
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['server:read'])]
    // @phpstan-ignore-next-line
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read'])]
    private ?string $currentVersion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['server:read'])]
    private ?\DateTimeInterface $lastUpdate = null;

    #[ORM\ManyToOne(inversedBy: 'apps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['server:read'])]
    private ?AppUpdateType $updateType = null;

    #[ORM\ManyToOne(inversedBy: 'apps')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Server $server = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['server:read'])]
    private ?string $updateResource = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['server:read'])]
    private ?string $extraUpdateResource = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['server:read'])]
    private ?string $latestVersion = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['server:read'])]
    private ?string $documentation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCurrentVersion(): ?string
    {
        return $this->currentVersion;
    }

    public function setCurrentVersion(string $currentVersion): static
    {
        $this->currentVersion = $currentVersion;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(?\DateTimeInterface $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    public function getUpdateType(): ?AppUpdateType
    {
        return $this->updateType;
    }

    public function setUpdateType(?AppUpdateType $updateType): static
    {
        $this->updateType = $updateType;

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

    public function getUpdateResource(): ?string
    {
        return $this->updateResource;
    }

    public function setUpdateResource(string $updateResource): static
    {
        $this->updateResource = $updateResource;

        return $this;
    }

    public function getExtraUpdateResource(): ?string
    {
        return $this->extraUpdateResource;
    }

    public function setExtraUpdateResource(?string $extraUpdateResource): static
    {
        $this->extraUpdateResource = $extraUpdateResource;

        return $this;
    }

    public function getLatestVersion(): ?string
    {
        return $this->latestVersion;
    }

    public function setLatestVersion(?string $latestVersion): static
    {
        $this->latestVersion = $latestVersion;

        return $this;
    }

    public function getDocumentation(): ?string
    {
        return $this->documentation;
    }

    public function setDocumentation(?string $documentation): static
    {
        $this->documentation = $documentation;

        return $this;
    }
}
