<?php

namespace App\Core;

/**
 * Classe Database
 * Singleton pour la gestion de la connexion à la base de données via PDO.
 *
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;

    private function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset'] ?? 'utf8mb4'
        );

        try {
            $this->connection = new \PDO($dsn, $config['user'], $config['password'], [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException("Connexion à la base de données impossible : " . $e->getMessage());
        }
    }

    private function __clone() {}
    public function __wakeup() { throw new \RuntimeException("Cannot unserialize singleton."); }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
