<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use App\Repository\AlbumRepository;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private AlbumRepository $albumRepository;

    public function __construct(AlbumRepository $albumRepository)
    {
        $this->albumRepository = $albumRepository;
    }

    public function getGlobals(): array
    {
        return [
            'albumActif' => $this->albumRepository->findOneBy(['active' => true]),
        ];
    }
}
