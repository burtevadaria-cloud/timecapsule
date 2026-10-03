<?php
// Creates the database tables (and the guest user) from database/schema.sql.
// Safe to run many times: the schema only creates what is missing.
//
//   php bin/init-db.php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $app = new App\App(dirname(__DIR__));
    $app->db->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));

    echo 'Database is ready (' . $app->db->host() . ").\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Could not initialise the database: ' . $e->getMessage() . "\n");
    exit(1);
}
