<?php 

namespace App\Components;

use App\Components\Curl;

class MultiCurl
{
    public $multiCurl;
    public $resultRequest = [];
    protected $nextCurlId = 0;
    protected $curls = [];
    protected $activeCurls = [];
    protected $concurrency = 10;
    protected $jsonDecoder;
    protected $paramSearch;
    protected $proxy;


    public function __construct($base_url = null)
    {
        $this->multiCurl = curl_multi_init();
    }

    /**
     * Add Get
     *
     * @access public
     * @param stirng $url
     */
    public function addGet($url)
    {
        $curl = new Curl();
        $curl->SetUrl($url);
        $this->queueHandle($curl);
    }

    /**
     * Add Curls
     *
     * @access public
     * @param array $urls
     */
    public function addCurls($urls)
    {
        foreach ($urls as $url) {
            $this->addGet($url);
        }
    }

    /**
     * Set Param Search
     *
     * @access public
     * @param string $arg
     */
    public function setParamSearch($arg)
    {
        $this->paramSearch = $arg;
    }

    /**
     * Set Proxy
     *
     * @access public
     * @param string $arg
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    protected function queueHandle($curl)
    {
        $curl->id = $this->nextCurlId++;
        $curl->isChildOfMultiCurl = true;
        $this->curls[$curl->id] = $curl;
    }

    /**
     * Set Param Search
     *
     * @access public
     * @param string $param
     */
    public function setJsonDecode($param)
    {
        $this->jsonDecoder = $param;
    }

    protected function initHandle($curl)
    {   
        if ($this->jsonDecoder) {
            $curl->setJsonDecode($this->jsonDecoder);
        }

        if ($this->proxy) {
            $curl->setProxy($this->proxy);
        }

        if ($this->paramSearch) {
            $curl->setParamSearch($this->paramSearch);
        }

        $curlm_error_code = curl_multi_add_handle($this->multiCurl, $curl->curl);
        if (!($curlm_error_code === CURLM_OK)) {
            throw new \ErrorException('cURL multi add handle error: ' . curl_multi_strerror($curlm_error_code));
        }
        $this->activeCurls[$curl->id] = $curl;
    }

    /**
     * Exec
     *
     * @access public
     *
     * @return array
     */
    public function exec()
    {
        $concurrency = $this->concurrency;
        if ($concurrency > count($this->curls)) {
            $concurrency = count($this->curls);
        }
        
        for ($i = 0; $i < $concurrency; $i++){
            $this->initHandle(array_shift($this->curls));
        }
        
        do {
            if (curl_multi_select($this->multiCurl) === -1) {
                usleep(100000);
            }

            curl_multi_exec($this->multiCurl, $active);
            
            while (!($info_array = curl_multi_info_read($this->multiCurl)) === false) {
                
                if ($info_array['msg'] === CURLMSG_DONE){
                    foreach ($this->activeCurls as $key => $ch) {
                        
                        if ($ch->curl === $info_array['handle']) {

                            $ch->curlErrorCode = $info_array['result'];
                            $ch->exec($ch->curl);
                            
                            if ($ch->repeatRequest()) {
                                curl_multi_remove_handle($this->multiCurl, $ch->curl);

                                curl_multi_add_handle($this->multiCurl, $ch->curl);
                            } else {
                                $this->resultRequest[$ch->codeOfSearch] = $ch->returnResponse();

                                unset($this->activeCurls[$key]);

                                if (count($this->curls) >= 1) {
                                    $this->initHandle(array_shift($this->curls));
                                }

                                curl_multi_remove_handle($this->multiCurl, $ch->curl);

                                $ch->close();
                            }

                            break;
                        }
                    }
                }
            }

            if (!$active) {
                $active = count($this->activeCurls);
            }
        } while ($active > 0);
       
        return $this->resultRequest;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close()
    {
        foreach ($this->curls as $curl) {
            $curl->close();
        }
        if (is_resource($this->multiCurl)) {
            curl_multi_close($this->multiCurl);
        }
    }
}