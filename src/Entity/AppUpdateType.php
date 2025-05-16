<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AppUpdateTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AppUpdateTypeRepository::class)]
#[ApiResource(
    description: 'Application',
    normalizationContext: [ 'groups' => ['server:read'] ]
)]
class AppUpdateType
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

    /**
     * @var Collection<int, App>
     */
    #[ORM\OneToMany(targetEntity: App::class, mappedBy: 'updateType')]
    private Collection $apps;

    public function __construct()
    {
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
            $app->setUpdateType($this);
        }

        return $this;
    }

    public function removeApp(App $app): static
    {
        if ($this->apps->removeElement($app)) {
            // set the owning side to null (unless already changed)
            if ($app->getUpdateType() === $this) {
                $app->setUpdateType(null);
            }
        }

        return $this;
    }
}
