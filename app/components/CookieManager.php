<?php 

namespace App\Components;

class CookieManager
{
	public static function set($name, $value, $expire = 0, $path = '/', $domain = 'current', $secure = 0)
	{
		if ($domain == 'current') {
			$domain = $_SERVER['HTTP_HOST'];
		}
		setcookie($name, $value, $expire, $path, $domain, $secure);
		return true;
	}

	public static function read($name)
	{
		if (isset($_COOKIE[$name])) {
			return $_COOKIE[$name];
		}
		return false;
	}

	public static function delete($name, $value = '', $expire = 1, $path = '/', $domain = 'current', $secure = 0)
	{
		if ($domain == 'current') {
			$domain = $_SERVER['HTTP_HOST'];
		}
		setcookie($name, $value, $expire, $path, $domain, $secure);
		return true;
	}
}