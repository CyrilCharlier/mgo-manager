<?php

namespace App\Entity;

use App\Repository\GroupCompteMembershipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupCompteMembershipRepository::class)]
class GroupCompteMembership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'Membership', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Compte $compte;

    #[ORM\ManyToOne(inversedBy: 'Memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private Group $groupe;

    #[ORM\Column]
    private bool $isAdmin = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompte(): Compte
    {
        return $this->compte;
    }

    public function setCompte(Compte $compte): static
    {
        $this->compte = $compte;

        return $this;
    }

    public function getGroupe(): ?Group
    {
        return $this->groupe;
    }

    public function setGroupe(?Group $groupe): static
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }
}
