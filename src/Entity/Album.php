<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $active = null;

    /**
     * @var Collection<int, Set>&Selectable
     */
    #[ORM\OneToMany(targetEntity: Set::class, mappedBy: 'album', orphanRemoval: true, fetch:"EAGER")]
    private Collection $sets;

    public function __construct()
    {
        $this->sets = new ArrayCollection();
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, Set>
     */
    public function getSets(): Collection
    {
        $criteria = Criteria::create()->orderBy(['page' => Order::Ascending]);
        return $this->sets->matching($criteria);;
    }

    public function addSet(Set $set): static
    {
        if (!$this->sets->contains($set)) {
            $this->sets->add($set);
            $set->setAlbum($this);
        }

        return $this;
    }

    public function removeSet(Set $set): static
    {
        if ($this->sets->removeElement($set)) {
            // set the owning side to null (unless already changed)
            if ($set->getAlbum() === $this) {
                $set->setAlbum(null);
            }
        }

        return $this;
    }

    public function getTotalCarte(): int
    {
        $total = 0;
        foreach ($this->sets as $set) {
            $total += count($set->getCartes());
        }
        return $total;
    }

    public function getGoldenCartes(): Collection
    {
        $cartes = new ArrayCollection();
        foreach ($this->sets as $set) {
            foreach ($set->getCartes() as $carte) {
                if($carte->isGolden())
                    $cartes->add($carte);
            }
        }
        return $cartes;
    }
}
