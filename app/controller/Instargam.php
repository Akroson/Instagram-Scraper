<?php 

namespace App\Controller;

use App\Components\Curl;
use App\Components\MultiCurl;
use App\Components\RequestUrl;
use App\Components\ParserJson;
use App\Model\DbProxy;

class Instagram
{
    private static $proxyUsed;

    public function __construct()
    {
        self::$proxyUsed = DbProxy::getProxy();
        echo self::$proxyUsed;
        if(!self::$proxyUsed) {
            throw new \Exception('some text');
        }
    }

    public function getUserMedia($user, $maxId = '')
    {
        $request = new Curl();
        $request->setUrl(RequestUrl::getUrlUserMedia($user, $maxId));
        $request->setJsonDecode('App\Components\ParserJson::parseJsonMedia');
        $request->setProxy(self::$proxyUsed);
        $result = $request->exec();

        return $result;
    }
    public function getUserCommentsByPost($param, $args)
    {
        $request = self::createCurlObj($args,'App\Components\RequestUrl::getUrlCommentByCode');
        $request->setParamSearch($param);
        $request->setJsonDecode('App\Components\ParserJson::parseJsonComment');
        //$request->setProxy(self::$proxyUsed);
        $result = $request->exec();

        return $result;
    }

    public function getUserLikeByPost($param, $args)
    {
        $request = self::createCurlObj($args,'App\Components\RequestUrl::getUrlLikeByCode');

        $request->setParamSearch($param);
        $request->setJsonDecode('App\Components\ParserJson::parseJsonLike');
        //$request->setProxy('101.78.219.178:8080');
        $result = $request->exec();
        return $result;
    }

    public function getUserFollowing($param, $args)
    {
        $request = new Curl();
        $request->setUrl(RequestUrl::getUrlFollowingById($id));
        $request->setParamSearch($param, false);
        $request->setJsonDecode('App\Components\ParserJson::parseJsonFollowing');
        $request->setProxy(self::$proxyUsed);
        $result = $request->exec();

        return $result;
    }

    public function getUserFollowers($id, $param)
    {
        $request = new Curl();
        $request->setUrl(RequestUrl::getUrlFollowersById($id));
        $request->setParamSearch($param, false);
        $request->setJsonDecode('App\Components\ParserJson::parseJsonFollowers');
        $request->setProxy(self::$proxyUsed);
        $result = $request->exec();

        return $result;
    }

    private static function createCurlObj($args, $method)
    {
        if (is_array($args) && count($args) > 1) {
            $obj = new MultiCurl;
            $links = [];
            $links = call_user_func_array($method, $args);
            $obj->addCurls($links);;
        } else {
            $obj = new Curl;
            $link = call_user_func_array($method, $args);
            $obj->setUrl($link);
        }

        return $obj;
    }   
}