<?php

namespace anacaona;

use PDO;
use PDOException;

class Database
{
    private static $host = 'localhost';
    private static $dbname = 'location_appartement';
    private static $user = 'root';
    private static $password = '';
    private static $pdo = null;

    public static function connect()
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8",
                    self::$user, 
                    self::$password
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}