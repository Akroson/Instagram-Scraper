<?php 

namespace App\Model;

use App\Config\Db;
use PDO;

class DbProxy
{
	public static function getProxy()
	{
		$db = Db::getConnection();
		$time = time();
		$request = $db->query("SELECT `proxy` FROM `proxy_table` WHERE ('$time' - `time_of_use`) > 300 LIMIT 1;
							UPDATE `proxy_table` SET `time_of_use` = '$time' WHERE `proxy` = '$result'");
		$result = $request->fetch();
		$result = $result['proxy'];

		$db = null;

		return $result;
	}

	public static function addProxySelect($proxy)
	{
		$db = Db::getConnection();
		$request = $db->prepare('INSERT INTO `select_proxy_table` (`proxy_select`) VALUE (:proxy)');

		foreach ($proxy as $item) {
			try {
				$request->bindParam(':proxy', $item, PDO::PARAM_STR);
	        	$request->execute();
			} catch (\PDOException $e) {
				continue;
			}
		}

		$db = null;

		return true;
	}

	public static function deleteProxySelect($proxy)
	{
		$db = Db::getConnection();
		$request = $db->prepare("DELETE FROM `select_proxy_table` WHERE `proxy_select` = :proxy");

		if (is_array($proxy)) {
			foreach($proxy as $item) {
				$request->bindParam(':proxy', $item, PDO::PARAM_STR);
				$request->execute();
			}
		} else {
			$request->bindParam(':proxy', $proxy, PDO::PARAM_STR);
			$request->execute();
		}

		$db = null;

		return true;
	}

	public static function getProxySelectList()
	{
		$db = Db::getConnection();
		$getProxy = $db->query('SELECT `proxy_select` FROM `select_proxy_table`');
		$proxy = $getProxy->fetchAll();

		return $proxy;
	}

	public static function moveToProxyList($proxy)
	{
		$db = Db::getConnection();
		$request = $db->prepare('START TRANSACTION; INSERT INTO `proxy_table` (`proxy`, `time_of_use`) VALUES(:proxy, :time_of_use); DELETE FROM `select_proxy_table` WHERE `proxy_select` = :proxy; COMMIT');

		if (is_array($proxy)) {
			foreach($proxy as $item) {
				$time = time();
				$request->bindParam(':proxy', $item, PDO::PARAM_STR);
				$request->bindParam(':time_of_use', $time, PDO::PARAM_STR);
				$request->execute();
			}
		} else {
			$time = time();
			$request->bindParam(':proxy', $proxy, PDO::PARAM_STR);
			$request->bindParam(':time_of_use', $time, PDO::PARAM_STR);
			$request->execute();
		}

		$db = null;

		return true;
	}
}