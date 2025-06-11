<?php

namespace App\Entity;

use App\Repository\SetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SetRepository::class)]
#[ORM\Table(name: '`set`')]
class Set
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'sets')]
    #[ORM\JoinColumn(nullable: false)]
    private Album $album;

    #[ORM\Column(nullable: false)]
    private int $page;

    /**
     * @var Collection<int, Carte> & Selectable<int, Carte>
     */ 
    #[ORM\OneToMany(targetEntity: Carte::class, mappedBy: 's', orphanRemoval: true, fetch:"EAGER")]
    private Collection $cartes;

    public function __construct()
    {
        $this->cartes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameListe(): ?string
    {
        return $this->name.' ['.$this->getAlbum()->getName().']';
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

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): static
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return Collection<int, Carte> & Selectable<int, Carte>
     */
    public function getCartes(): Collection
    {
        $criteria = Criteria::create()->orderBy(['num' => Order::Ascending]);
        return $this->cartes->matching($criteria);
    }

    public function addCarte(Carte $carte): static
    {
        if (!$this->cartes->contains($carte)) {
            $this->cartes->add($carte);
            $carte->setS($this);
        }

        return $this;
    }

    public function removeCarte(Carte $carte): static
    {
        if ($this->cartes->removeElement($carte)) {
            // set the owning side to null (unless already changed)
            if ($carte->getS() === $this) {
                $carte->setS(null);
            }
        }

        return $this;
    }
}
