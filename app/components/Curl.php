<?php 

namespace App\Components;

use App\Config\ConstName;
use App\Components\ParserXml;
use App\Components\ParserJson;

class Curl
{
    public $id;
    public $isChildOfMultiCurl = false;
    public $curl;
    public $response;
    public $baseUrl;
    public $codeOfSearch;
    public $fileHandle = null;
    public $error;
    public $curlErrorMessage;
    public $curlErrorCode;

    protected $proxy;
    protected $getResponse;

    private $attemps = 1;
    private $retryRequest = false;
    private $requestPatter;
    private $paramSearch;
    private $jsonDecoder;
    private $jsonResponse;
    private $xmlPattenSearch;
    private $xmlDecoder;
    private $endPoint;
    private $stepPagination;
    public $cookie = [];

    public function __construct()
    {
        $this->curl = curl_init();
        $this->setOpt(CURLOPT_HEADER, false);
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $this->setOpt(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36');
    }

    /**
     * Download
     *
     * @access public
     * @param sting $url
     * @param sting $fileName
     *
     * @return bool
     */
    public function download($url, $fileName)
    {
        
        //need to improve
        $mode = 'wb';
        $this->fileHandle = fopen($fileName, $mode);
        $this->setOpt(CURLOPT_FILE, $this->fileHandle);
        $this->setUrl($url);
        $this->exec();

        return !$this->error;
    }

    /**
     * Set Opt
     *
     * @access public
     * @param const $option
     * @param sting $value
     */
    public function setOpt($option, $value)
    {
        curl_setopt($this->curl, $option, $value);
    }

    /**
     * Set Url
     *
     * @access public
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->setOpt(CURLOPT_URL, $url);
        $this->baseUrl = $url;
    }

    /**
     * Set Param Search
     *
     * @access public
     * @param string $arg
     * @param bool $saveCodeOfSearch
     */
    public function setParamSearch($arg, $saveCodeOfSearch = true)
    {
        $this->paramSearch = $arg;
        if ($saveCodeOfSearch) {
            $this->setCodeOfSearch();
        }
    }

    /**
     * Set Xml Pattern Search
     *
     * @access public
     * @param string $pattern
     */
    public function setXmlPatternSearch($pattern)
    {
        $this->xmlPattenSearch = $pattern;
    }

    /**
     * Set Proxy
     *
     * @access public
     * @param string $proxy
     */
    public function setProxy($proxy)
    {
        $this->setOpt(CURLOPT_PROXY, $proxy);
        $this->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        // $this->setOpt(CURLOPT_TIMEOUT, 9);
        // $this->setOpt(CURLOPT_CONNECTTIMEOUT, 10);
    }

    /**
     * Set Json Decode
     *
     * @access public
     * @param string $method
     */
    public function setJsonDecode($method)
    {
        $this->jsonResponse = true;
        $this->jsonDecoder = $method;
    }

    /**
     * Set Xml Decode
     *
     * @access public
     * @param string $method
     */
    public function setXmlDecode($method)
    {
        $this->jsonResponse = false;
        $this->xmlDecoder = $method;
    }

    /**
     * Set Step Pagination
     *
     * @access public
     * @param int $step
     */
    public function setStepPagination($step)
    {
        $this->stepPagination = $step;
    }

    /**
     * Set Requst Pattern
     *
     * @access public
     * @param string $patter
     */
    public function setRequestPatter($patter)
    {
        $this->requestPatter = $patter;
    }

    private function setCodeOfSearch()
    {
        preg_match('/[&"](?:"?shortcode"?[=:])"?([^&"]+)"?/i', $this->baseUrl, $result);
        $this->codeOfSearch = $result[1];
    }

    /**
     * Parse Response
     *
     * @access private
     * @param mixed $patter
     *
     * @return array
     */
    private function parseResponse($response_body)
    {
        $args = [];
        $response = $response_body;

        if ($this->jsonResponse) {
            $args[] = $response;
            if ($this->paramSearch) {
                $args[] = $this->paramSearch;
            }
            $result = call_user_func_array($this->jsonDecoder, $args);
            return $result;
        } else {
            $args[] = $response;
            if ($this->xmlPattenSearch) {
                $args[] = $this->xmlPattenSearch;
            }
            $result = call_user_func_array($this->xmlDecoder, $args);
            return $result;
        }
    }

    /**
     * Exec
     *
     * @access public
     * @param null $curl
     *
     * @return mixed
     */
    public function exec($curl = null)
    {
        if ($curl === null) {
            $this->getResponse = curl_exec($this->curl);
            $this->curlErrorCode = curl_errno($this->curl);
            $this->curlErrorMessage = curl_error($this->curl);
        } else {
            $this->getResponse = curl_multi_getcontent($curl);
            $this->curlErrorMessage = curl_error($curl);
        }

        $this->error = !($this->curlErrorCode === 0);

        if ($this->jsonDecoder || $this->xmlDecoder) {
            $this->handlingResponse($this->getResponse);
        }
        $this->getResponse = null;

        if ($this->isChildOfMultiCurl) {
            return;
        }

        if ($this->repeatRequest()) {
            return $this->exec();
        }

        $this->execDone();

        return $this->returnResponse();
    }

    public function execDone()
    {
        if ($this->fileHandle !== null) {
            if (is_resource($this->fileHandle)) {
                fclose($this->fileHandle);
            }
        }
    }

    private function handlingResponse($response)
    {
        $arrResponse = $this->parseResponse($response);

        $this->retryRequest = array_pop($arrResponse);
        if ($this->retryRequest && $this->paramSearch) {
            $this->endPoint = array_pop($arrResponse);
        }

        $this->response = $this->response ? array_merge($this->response, $arrResponse) : $arrResponse;
    }

    /**
     * Return Response
     *
     * @access public
     *
     * @return mixed
     */
    public function returnResponse()
    {
        if ($this->isChildOfMultiCurl) {
            return $this->response;
        } else if ($this->codeOfSearch) {
            $arr = [];
            $arr[$this->codeOfSearch] = $this->response;
            return $arr;
        } else if ($this->jsonResponse){
            $arr = [];
            $arr[ConstName::REPEAT] = $this->retryRequest;
            $arr[ConstName::RESPONSE] = $this->response;
            return $arr;
        }

        return $this->response;
    }

    /**
     * Post
     *
     * @access public
     * @param stirng $url
     * @param array $param
     *
     * @return mixed
     */
    public function post($url, $param)
    {
        $this->setUrl($url);
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $param);
        return $this->exec();
    }

    /**
     * Repeat Request
     *
     * @access public
     *
     * @return bool
     */
    public function repeatRequest()
    {
        $repeat = false;
        if ($this->error) {
            if ($this->attemps) {
                $repeat = true;
                $this->attemps -= 1;
            } else {
                $this->response = false;
            }

        } else if ($this->retryRequest && $this->endPoint && $this->jsonResponse) {
            $repeat = true;
            $this->baseUrl = preg_replace_callback('/([&"]?after"?[=:]"?)([^&"]*)("?)/i',
            function ($arr) 
            {
                $arr[2] = $this->endPoint;
                return $arr[1] . $arr[2] . $arr[3];
            },
            $this->baseUrl);
            $this->setOpt(CURLOPT_URL, $this->baseUrl);
        } else if ($this->retryRequest && !$this->jsonResponse) {
            $repeat = true;
            $this->baseUrl = str_replace('{{count}}', $this->stepPagination, $this->requestPatter);
            $this->stepPagination += $this->stepPagination;
            $this->setOpt(CURLOPT_URL, $this->baseUrl);
        }
        return $repeat;
    }
    
    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
    }
}