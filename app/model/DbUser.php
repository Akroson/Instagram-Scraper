<?php 

namespace App\Model;

use App\Config\Db;
use PDO;

class DbUser
{
	public static function checkUserByIp($ip)
	{
		$db = Db::getConnection();
		$request = $db->query("SELECT `user_activity` FROM `user` WHERE `user_ip` = '$ip'");
		$result = $request->fetch();
		$db = null;

		if ($result) {
			$result = $result['user_activity'];
			return $result;
		}
		return false;
	}

	public static function checkUserByHash($hash)
	{
		$db = Db::getConnection();
		$request = $db->query("SELECT * FROM `user` WHERE `user_hash` = '$hash'");
		$result = $request->fetch();
		$db = null;

		return $result ? $result : false;
	}

	public static function updateUserAcivity($ip, $newUserhash)
	{
		$db = Db::getConnection();
		$time = time();
		$request = $db->query("SELECT `user_archive` FROM `user` WHERE `user_ip` = '$ip'; 
						UPDATE `user` SET `user_activity` = '$time', `user_hash` = '$newUserhash' WHERE `user_ip` = '$ip'");
		$result = $request->fetch();
		$result = $result['user_archive'];
		$db = null;

		return $result;
	}

	public static function writeUserToTable($ip, $hash, $archive)
	{
		$db = Db::getConnection();
		$time = time();

		try {
			$request = $db->query("INSERT INTO `user` (`user_ip`,`user_hash`,`user_archive`,`user_activity`) VALUE ('$ip','$hash','$archive','$time')");

			$db = null;
			return true;
		} catch (\PDOException $e) {
			
			$db = null;
			return false;
		}
	}
}