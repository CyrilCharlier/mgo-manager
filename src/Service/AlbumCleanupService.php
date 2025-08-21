<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class AlbumCleanupService
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Supprime tous les albums inactifs (active = 0) 
     * et les entités liées : sets, cartes, cartes_obtenues
     */
    public function cleanupInactiveAlbums(): int
    {
        // On encapsule dans une transaction pour éviter incohérences
        return $this->connection->transactional(function (Connection $conn) {
            // Supprimer cartes obtenues liées aux albums inactifs
            $conn->executeStatement("
                DELETE FROM carte_obtenue
                WHERE carte_id IN (
                    SELECT c.id
                    FROM carte c
                    INNER JOIN `set` s ON c.s_id = s.id
                    INNER JOIN album a ON s.album_id = a.id
                    WHERE a.active = 0
                )
            ");

            // Supprimer cartes liées
            $conn->executeStatement("
                DELETE FROM carte
                WHERE s_id IN (
                    SELECT s.id
                    FROM `set` s
                    INNER JOIN album a ON s.album_id = a.id
                    WHERE a.active = 0
                )
            ");

            // Supprimer sets liés
            $conn->executeStatement("
                DELETE FROM `set`
                WHERE album_id IN (
                    SELECT a.id
                    FROM album a
                    WHERE a.active = 0
                )
            ");

            // Supprimer albums inactifs
            $deleted = $conn->executeStatement("
                DELETE FROM album
                WHERE active = 0
            ");

            return $deleted; // Nombre d’albums supprimés
        });
    }
}
