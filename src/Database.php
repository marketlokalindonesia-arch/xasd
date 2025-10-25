<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../config/config.php';
        $dbPath = $config['db']['path'];
        
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function initializeSchema()
    {
        $schema = file_get_contents(__DIR__ . '/../config/schema.sql');
        try {
            $this->pdo->exec($schema);
            return true;
        } catch (PDOException $e) {
            error_log('Schema initialization failed: ' . $e->getMessage());
            return false;
        }
    }
}
