<?php

namespace anacaona;

use PDO;
use PDOException;

class Database extends PDO{

	public static $pdo;
	private static $host = 'localhost';
	private static  $user = 'root';
	private static  $dbname = 'anacaona';
	private  static  $password = '';



	public static function connect(){
		try
		{
			if (self::$pdo===null) {
				$pdo=new PDO("mysql:host=".self::$host.";dbname=".self::$dbname,self::$user,self::$password);
				$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
				self::$pdo=$pdo;
			}
			return self::$pdo;
		}
		catch(PDOException $e)
		{
			echo "erreur de connexion à la base de donnée".$e;
		}
	}
}