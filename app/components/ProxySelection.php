<?php 

namespace App\Components;

class ProxySelection extends Curl
{
	public function exec($curl = null)
	{
		if ($curl === null) {
            $this->getResponse = curl_exec($this->curl);
            $this->curlErrorMessage = curl_error($this->curl);
        } else {
            $this->getResponse = curl_multi_getcontent($curl);
            $this->curlErrorMessage = curl_error($curl);
        }
        
        if ($this->curlErrorMessage) {
            return false;
        }

        if ($this->getResponse) {
            $this->getResponse = null;
            return true;
        } else {
            return false;
        }
	}
}