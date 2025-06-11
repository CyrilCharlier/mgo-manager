<?php

namespace App\Entity;

use App\Repository\CarteObtenueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarteObtenueRepository::class)]
class CarteObtenue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'carteObtenues')]
    #[ORM\JoinColumn(nullable: false)]
    private Carte $carte;

    #[ORM\ManyToOne(inversedBy: 'carteObtenues')]
    #[ORM\JoinColumn(nullable: false)]
    private Compte $compte;

    #[ORM\Column]
    private int $nombre;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCarte(): ?Carte
    {
        return $this->carte;
    }

    public function setCarte(?Carte $carte): static
    {
        $this->carte = $carte;

        return $this;
    }

    public function getCompte(): ?Compte
    {
        return $this->compte;
    }

    public function setCompte(?Compte $compte): static
    {
        $this->compte = $compte;

        return $this;
    }

    public function getNombre(): ?int
    {
        return $this->nombre;
    }

    public function setNombre(int $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }
}
