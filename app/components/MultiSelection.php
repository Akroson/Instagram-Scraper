<?php

namespace App\Components;

use App\Components\MultiCurl;
use App\Components\ProxySelection;

class MultiSelection extends MultiCurl
{
	public function addGet($url)
    {
        $curl = new ProxySelection();
        $curl->SetUrl($url);
        $this->queueHandle($curl);
    }

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
                            $result = $ch->exec($ch->curl);

                            if($result) {
                            	unset($this->activeCurls[$key]);

                            	if (count($this->curls) >= 1) {
                                    $this->initHandle(array_shift($this->curls));
                                }

                                curl_multi_remove_handle($this->multiCurl, $ch->curl);

                                $ch->close();
                            } else {
                            	throw new \Exception("Error Processing Request");
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
       
        return true;
    }
}