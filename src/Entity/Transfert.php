<?php

namespace App\Entity;

class Transfert
{
    private ?Compte $cFrom = null;
    private ?Compte $cTo = null;
    private ?Carte $carte = null;

    public function getCFrom(): ?Compte
    {
        return $this->cFrom;
    }

    public function setCFrom(?Compte $cFrom): static
    {
        $this->cFrom = $cFrom;

        return $this;
    }

    public function getcTo(): ?Compte
    {
        return $this->cTo;
    }

    public function setCTo(?Compte $cTo): static
    {
        $this->cTo = $cTo;

        return $this;
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
}