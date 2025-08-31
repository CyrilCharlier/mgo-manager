<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * @var Collection<int, GroupCompteMembership>
     */
    #[ORM\OneToMany(targetEntity: GroupCompteMembership::class, mappedBy: 'groupe', orphanRemoval: true)]
    private Collection $Memberships;

    public function __construct()
    {
        $this->Memberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, GroupCompteMembership>
     */
    public function getMemberships(): Collection
    {
        return $this->Memberships;
    }

    public function addMembership(GroupCompteMembership $membership): static
    {
        if (!$this->Memberships->contains($membership)) {
            $this->Memberships->add($membership);
            $membership->setGroupe($this);
        }

        return $this;
    }

    public function removeMembership(GroupCompteMembership $membership): static
    {
        if ($this->Memberships->removeElement($membership)) {
            // set the owning side to null (unless already changed)
            if ($membership->getGroupe() === $this) {
                $membership->setGroupe(null);
            }
        }

        return $this;
    }
}
