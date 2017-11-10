<?php

namespace App\Config;

class Db
{
	private static $host = 'localhost';
	private static $dbname = 'inst_sharp_base';
	private static $name = 'root';
	private static $password = '';

	public static function getConnection()
	{
		$dsn ='mysql:host='. Db::$host .';dbname='. Db::$dbname .'';
		$opt = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
			];

		$db = new \PDO($dsn, Db::$name, Db::$password, $opt);
		//$db->exec("set names utf8");

		return $db;
	}
}