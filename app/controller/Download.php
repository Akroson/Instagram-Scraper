<?php 

namespace App\Controller;

use App\Model\DbUser;
use App\Components\Curl;
use App\Components\CookieManager;
use ZipArchive;

class Download
{
	private $ROOT;
	private $pathTmpFoto;
	private $pathZipArchive;
	private $filePatter = '#^https://[^\s]+/(([\w]+)([\.]jpg|png]))$#';
	private $downloadFileList = [];
	private $userIp;
	private $userArchive;
	private $userHash;
	private $newUser = true;

	/**
     * Construct
     *
     * @access public
     */
	public function __construct()
	{
		$this->ROOT = $_SERVER['DOCUMENT_ROOT'];
		$this->pathTmpFoto = $this->ROOT . '/userStorage/tmpFile/';
		$this->pathZipArchive = $this->ROOT . '/userStorage/zipArchive/';
		$this->userIp = self::getUserIp();
	}

	 /**
     * Create User Archive
     *
     * @access public
     * @param array $urls
     */
	public function createUserArchive($urls)
	{
		if($this->checkUser()) {
			if ($this->newUser) {
				$this->registerUser();
			}
			$curl = new Curl;
			foreach ($urls as $url) {
				if (preg_match($this->filePatter, $url, $arr)) {
					$fileName = md5($this->userHash . $arr[2]) . $arr[3];
					$filePath = $this->pathTmpFoto . $fileName;
					if ($curl->download($url, $filePath)) {
						$this->downloadFileList[$fileName] = $filePath;
					}
				}
			}
			$curl->close();
			$this->createArchive();
			CookieManager::set('download', 'complete', time() + (60*60*24));
		}
	}

	/**
     * Transfer User Archive
     *
     * @access public
     * @param string $hash
     */
	public function transferUserArchive($hash)
	{
		$userInfo = DbUser::checkUserByHash($hash);
		if ($userInfo) {
			if ((time() - $userInfo['user_activity']) > 3600) return false; //1 hours 
			$file = $this->pathZipArchive . $userInfo['user_archive'];
			if(file_exists($file)) {

				//Use X-SendFile for Apach and X-Accel-Redirect for Ngnix
				header('X-SendFile: ' . realpath($file));
				header('Content-Length: ' . filesize($file));
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename="FotoArchive.zip"');
			}
		}
	}

	/**
     * Create Archive
     *
     * @access private
     */
	private function createArchive()
	{
		$zip = new ZipArchive;
		$zipFile = $this->pathZipArchive . $this->userArchive;
		if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
			foreach ($this->downloadFileList as $fileName => $filePath) {
				$zip->addFile($filePath, $fileName);
				$zip->setCompressionName($fileName, ZipArchive::CM_DEFAULT);
			}
			$zip->close();
		} else {
			$this->deleteDownloadFile();
		}
		$this->deleteDownloadFile();
	}

	/**
     * Delete Download FIle
     *
     * @access private
     */
	private function deleteDownloadFile()
	{
		while($this->downloadFileList) {
			unlink(array_shift($this->downloadFileList));
		}
	}

	/**
     * Register User
     *
     * @access private
     */
	private function registerUser()
	{
		$this->userHash = self::getRandomToken();
		$this->userArchive = md5($this->userHash . self::getRandomToken(5)) . '.zip';
		if (!DbUser::writeUserToTable($this->userIp, $this->userHash, $this->userArchive)) {
			$this->registerUser();
		}
	}

	/**
     * Check User
     *
     * @access private
     *
     * @return bool
     */
	private function checkUser()
	{
		if (CookieManager::read('download')) {
			return false;
		} else if (($infoActivity = DbUser::checkUserByIp($this->userIp)) !== false) {
			$time = time();
			$timeInterval = $time - $infoActivity;
			if ($timeInterval < 86400) { //24 hours
				$timeCookieLife = $time - $timeInterval;
				CookieManager::set('download', 'complete', $timeCookieLife);
				return false;
			}
			$this->newUser = false;
			$this->userHash = $this->getRandomToken();
			$this->userArchive = DbUser::updateUserAcivity($this->userIp, $this->userHash);
		}

		return true;
	}

	/**
     * Get Random Token
     *
     * @access private
     * @param int $length
     *
     * @return sting
     */
	private static function getRandomToken($length = 20)
	{
		if (function_exists('random_bytes')) {
		    return bin2hex(random_bytes($length));
		}
		if (function_exists('mcrypt_create_iv')) {
		    return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
		} 
		if (function_exists('openssl_random_pseudo_bytes')) {
		    return bin2hex(openssl_random_pseudo_bytes($length));
		}
	}

	/**
     * Get User Ip
     *
     * @access private
     *
     * @return sting
     */
	private static function getUserIp()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip;
	}
}