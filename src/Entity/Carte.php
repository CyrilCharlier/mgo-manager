<?php

namespace App\Entity;

use App\Repository\CarteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarteRepository::class)]
class Carte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $nbetoile = null;

    #[ORM\Column]
    private ?int $num = null;

    #[ORM\ManyToOne(inversedBy: 'cartes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Set $s = null;

    #[ORM\Column(nullable: false)]
    private ?bool $golden = null;

    /**
     * @var Collection<int, CarteObtenue>
     */
    #[ORM\OneToMany(targetEntity: CarteObtenue::class, mappedBy: 'carte', orphanRemoval: true)]
    private Collection $carteObtenues;

    #[ORM\Column]
    private ?bool $transferable = null;

    public function __construct()
    {
        $this->carteObtenues = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return ($this->isGolden() ? '✨ '.$this->name : $this->name);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNbetoile(): ?int
    {
        return $this->nbetoile;
    }

    public function setNbetoile(int $nbetoile): static
    {
        $this->nbetoile = $nbetoile;

        return $this;
    }

    public function getNum(): ?int
    {
        return $this->num;
    }

    public function setNum(int $num): static
    {
        $this->num = $num;

        return $this;
    }

    public function getS(): ?Set
    {
        return $this->s;
    }

    public function setS(?Set $s): static
    {
        $this->s = $s;

        return $this;
    }

    public function isGolden(): ?bool
    {
        return $this->golden;
    }

    public function setGolden(?bool $golden): static
    {
        $this->golden = $golden;

        return $this;
    }

    /**
     * @return Collection<int, CarteObtenue>
     */
    public function getCarteObtenues(): Collection
    {
        return $this->carteObtenues;
    }

    public function addCarteObtenue(CarteObtenue $carteObtenue): static
    {
        if (!$this->carteObtenues->contains($carteObtenue)) {
            $this->carteObtenues->add($carteObtenue);
            $carteObtenue->setCarte($this);
        }

        return $this;
    }

    public function removeCarteObtenue(CarteObtenue $carteObtenue): static
    {
        if ($this->carteObtenues->removeElement($carteObtenue)) {
            // set the owning side to null (unless already changed)
            if ($carteObtenue->getCarte() === $this) {
                $carteObtenue->setCarte(null);
            }
        }

        return $this;
    }

    public function isTransferable(): ?bool
    {
        return $this->transferable;
    }

    public function setTransferable(bool $transferable): static
    {
        $this->transferable = $transferable;

        return $this;
    }
}
