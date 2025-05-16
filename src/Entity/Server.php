<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ServerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
#[ApiResource(
    description: 'Serveurs',
    order: ['name' => 'ASC'],
    normalizationContext: [ 'groups' => ['server:read', 'logs'] ],
    denormalizationContext: [ 'groups' => ['server:write'] ],
    operations: [
        new GetCollection(),
        new Post(),
        new Get(),
        new Put(),
        new Delete(),
        new Patch(),
        /*
        new Post(
            name: 'updated',
            uriTemplate: '/servers/{id}/logs',
            routeName: 'api_servers_updated',
            requirements: ['id' => '\d+']
        )*/
    ]
)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['server:read', 'logs'])]
    // @phpstan-ignore-next-line
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['server:read', 'server:write', 'logs'])]
    private ?string $name = null;

    #[ORM\Column(length: 40, nullable: true)]
    #[Groups(['server:read', 'server:write'])]
    private ?string $ip = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['server:read', 'server:write'])]
    private ?\DateTimeInterface $lastUpdate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['server:read', 'server:write'])]
    private ?\DateTimeInterface $lastCheck = null;

    #[ORM\ManyToOne(inversedBy: 'servers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['server:read', 'server:write'])]
    private ?ServerType $type = null;

    /**
     * @var Collection<int, Log>
     */
    #[ORM\OneToMany(targetEntity: Log::class, mappedBy: 'server', orphanRemoval: true)]
    private Collection $logs;

    /**
     * @var Collection<int, App>
     */
    #[Groups(['server:read'])]
    #[ORM\OneToMany(targetEntity: App::class, mappedBy: 'server', orphanRemoval: true)]
    private Collection $apps;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['server:read', 'server:write'])]
    private ?string $documentation = null;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->apps = new ArrayCollection();
    }

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

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

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

    public function getLastCheck(): ?\DateTimeInterface
    {
        return $this->lastCheck;
    }

    public function setLastCheck(?\DateTimeInterface $lastCheck): static
    {
        $this->lastCheck = $lastCheck;

        return $this;
    }

    public function getType(): ?ServerType
    {
        return $this->type;
    }

    public function setType(?ServerType $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, Log>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): static
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setServer($this);
        }

        return $this;
    }

    public function removeLog(Log $log): static
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getServer() === $this) {
                $log->setServer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, App>
     */
    public function getApps(): Collection
    {
        return $this->apps;
    }

    public function addApp(App $app): static
    {
        if (!$this->apps->contains($app)) {
            $this->apps->add($app);
            $app->setServer($this);
        }

        return $this;
    }

    public function removeApp(App $app): static
    {
        if ($this->apps->removeElement($app)) {
            // set the owning side to null (unless already changed)
            if ($app->getServer() === $this) {
                $app->setServer(null);
            }
        }

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
