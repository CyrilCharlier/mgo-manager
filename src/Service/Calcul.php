<?php 

namespace App\Service;

use App\Entity\Compte;
use App\Entity\Album;
use Psr\Log\LoggerInterface;

class Calcul
{
    public function __construct(private LoggerInterface $logger) {}

    public function calculerEtoilesDoublees(Compte $compte, Album $album): int
    {
        $totE = 0;
        $cas = $compte->getCarteObtenues();
        foreach ($cas as $ca) {
            // Ne compte les cartes que de l'album en cours ET seulement si le nombre de cartes est supérieur à 1
            if ($ca->getCarte()->getS()->getAlbum() == $album && $ca->getNombre() > 1)
            {
                $nombre = $ca->getNombre()-1;
                $ajout = $nombre * $ca->getCarte()->getNbetoile();	
                $totE += $ajout;
                $this->logger->Info("Carte: {$ca->getCarte()->getName()} | nb: {$nombre} | étoiles: {$ca->getCarte()->getNbetoile()} | ajout: {$ajout} | Total: {$totE}");
            }
        }
        $this->logger->debug("Compte: {$compte->getName()} | Album: {$album->getName()} | Total étoiles doublées: $totE");
        return $totE;
    }

    public function calculerNombreCartesObtenuesSurAlbum(Compte $compte, Album $album): int
    {
        $totE = 0;
        $cos = $compte->getCarteObtenues();
        foreach ($cos as $co) {
            // Ne compte les cartes que de l'album
            if ($co->getCarte()->getS()->getAlbum() == $album)
            {
                $totE += 1;
            }
        }
        $this->logger->debug("Compte: {$compte->getName()} | Album: {$album->getName()} | Total cartes: $totE");
        return $totE;
    }
}
