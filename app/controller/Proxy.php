<?php 

namespace App\Controller;

use App\Model\DbProxy;
use App\Components\Curl;
use App\Components\MultiSelection;
use App\Components\RequestUrl;
use App\Config\ParseConf;
use App\Config\TestInstCode;

class Proxy
{
	/**
     * Refresh Select List
     *
     * @access public
     *
     * @return bool
     */
	public function refreshSelectList()
	{
		$site = ParseConf::PARAM;
		$result = [];

		foreach ($site as $siteParse) {
		    $curl = new Curl;
		    $curl->setXmlDecode($siteParse['method']);
		    $curl->setXmlPatternSearch($siteParse['patternSearch']);

		    if ($siteParse['morePage']) {

		        $step = $siteParse['morePage']['step'];
		        $requestPattern = $siteParse['morePage']['requestPattern'];
		        $nowStep = 0;
		        $curl->setUrl(str_replace('{{count}}', $nowStep, $requestPattern));
		        $curl->setStepPagination($siteParse['morePage']['step']);
		        $curl->setRequestPatter($requestPattern);
		        $resultParse = $curl->exec();
		        $result = $result ? array_merge($result, $resultParse) : $resultParse;

		    } else {
		            $curl->setUrl($siteParse['URL']);
		            $resultParse = $curl->exec();
		            $result = $result ? array_merge($result, $resultParse) : $resultParse;
		    }
		    
		    $curl->close();
		}

		echo '<pre>';print_r($result);

		DbProxy::addProxySelect($result);

		return true;
	}

	/**
     * Proxy Selection
     *
     * @access private
     *
     * @return bool
     */
	public function proxySelection()
	{
		$proxys = DbProxy::getProxySelectList();

		$curl = new MultiSelection;
		$code = TestInstCode::CODE;
		$links = [];
        $links = call_user_func_array('App\Components\RequestUrl::getUrlLikeByCode', $code);
        $result = [];

        foreach($proxys as $proxy) {
        	$curl->addCurls($links);
			$curl->setProxy($proxy['proxy_select']);
			try {
				$curl->exec();
				$result['ok'][] = $proxy['proxy_select'];
			} catch (\Exception $e) {
				$result['error'][] = $proxy['proxy_select'];
				continue;
			}
        }

        DbProxy::moveToProxyList($result['ok']);
		DbProxy::deleteProxySelect($result['error']);
        $proxys = null;
		$db = null;

		return true;
	}
}