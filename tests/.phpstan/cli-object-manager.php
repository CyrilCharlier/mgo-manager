<?php

use App\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$kernel = new Kernel('test', true);
$kernel->boot();

// Charge les variables d'environnement Symfony
$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->loadEnv(dirname(__DIR__) . '/.env.local');


return $kernel->getContainer()->get('doctrine')->getManager();
