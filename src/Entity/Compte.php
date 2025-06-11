<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use App\Repository\CompteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteRepository::class)]
class Compte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mgo = null;

    #[ORM\ManyToOne(inversedBy: 'comptes')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var Collection<int, CarteObtenue>
     */
    #[ORM\OneToMany(targetEntity: CarteObtenue::class, mappedBy: 'compte', orphanRemoval: true, cascade: ['persist'])]
    private Collection $carteObtenues;

    /**
     * @var Collection<int, Historique>
     */
    #[ORM\OneToMany(targetEntity: Historique::class, mappedBy: 'compte', cascade: ['remove'], orphanRemoval: true)]
    private Collection $historiques;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->historiques = new ArrayCollection();
        $this->carteObtenues = new ArrayCollection();
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

    public function getMgo(): ?string
    {
        return $this->mgo;
    }

    public function setMgo(?string $mgo): static
    {
        $this->mgo = $mgo;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
            $carteObtenue->setCompte($this);
        }

        return $this;
    }

    public function removeCarteObtenue(CarteObtenue $carteObtenue): static
    {
        if ($this->carteObtenues->removeElement($carteObtenue)) {
            // set the owning side to null (unless already changed)
            if ($carteObtenue->getCompte() === $this) {
                $carteObtenue->setCompte(null);
            }
        }

        return $this;
    }

    public function getCarteObtenue(Carte $carte): ?CarteObtenue
    {
        foreach ($this->carteObtenues as $carteObtenue) {
            if ($carteObtenue->getCarte() === $carte) {
                return $carteObtenue;
            }
        }

        return null;
    }

    public function getEtoileDouble(Album $a): ?int
    {
        $totE = 0;
        $cas = $this->getCarteObtenues();

        foreach ($cas as $ca) {
            // Ne compte les cartes que de l'album en cours
            if ($ca->getCarte()->getS()->getAlbum()) {
                $totE += ($ca->getNombre()-1)*$ca->getCarte()->getNbetoile();
            }
        }

        return $totE;
    }

    /**
     * @return Collection<int, Historique>
     */
    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): static
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setCompte($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getCompte() === $this) {
                $historique->setCompte(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
