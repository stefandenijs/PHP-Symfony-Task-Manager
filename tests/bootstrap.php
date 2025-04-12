<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

passthru("php bin/console --env=test doctrine:database:drop --force");
passthru("php bin/console --env=test doctrine:database:create");
passthru("php bin/console --env=test doctrine:schema:create");
passthru("php bin/console --env=test doctrine:fixtures:load -n");
