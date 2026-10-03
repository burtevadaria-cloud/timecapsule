<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

// A small wrapper around PDO (PostgreSQL).
// The connection is opened on first use, so pages that never touch the database
// (and the error page when the database is down) still work.
class Database
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $host,
        private readonly string $port,
        private readonly string $name,
        private readonly string $user,
        private readonly string $password,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            $config->getString('DB_HOST', 'localhost'),
            $config->getString('DB_PORT', '5432'),
            $config->getString('DB_NAME', 'timecapsule'),
            $config->getString('DB_USER', 'timecapsule'),
            $config->getString('DB_PASSWORD', 'secret'),
        );
    }

    public function connect(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->name}";

            $this->pdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // SQL errors throw exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // rows as ['column' => value]
                PDO::ATTR_TIMEOUT => 5,                             // fail fast if the DB is down
            ]);

            // Columns are TIMESTAMP without time zone and always hold UTC values.
            $this->pdo->exec("SET TIME ZONE 'UTC'");
        }

        return $this->pdo;
    }

    // Run a query with "?" placeholders. Values are sent separately from the SQL,
    // so user input can never change the query (no SQL injection).
    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->connect()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    // The first row, or null when there is none.
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    // Run SQL without parameters. Unlike query(), this can run many statements at once
    // (used to load database/schema.sql).
    public function exec(string $sql): void
    {
        $this->connect()->exec($sql);
    }

    public function host(): string
    {
        return $this->host;
    }

    // Turn common database errors into a short hint for the person running the app.
    // Returns null for any other error.
    public static function problemHint(Throwable $e): ?string
    {
        if (!$e instanceof PDOException) {
            return null;
        }

        $message = $e->getMessage();
        if (str_contains($message, 'SQLSTATE[42P01]')) {       // undefined table
            return 'The database has no tables yet. Run: php bin/init-db.php';
        }
        if (str_contains($message, 'SQLSTATE[08')) {           // connection errors (08xxx)
            return 'Cannot connect to the database. Check DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD.';
        }
        return null;
    }
}
