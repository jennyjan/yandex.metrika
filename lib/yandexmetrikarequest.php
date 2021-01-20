<?php

namespace Yandex\Metrika;

class YandexMetrikaRequest
{
    protected $url = 'https://api-metrika.yandex.net/stat/v1/data';
    private $counterId = '1234567';
    private $lang = 'ru';

    public function __construct($token = '')
    {
        $this->token = $token;
        $this->initHeader();
    }
    
    public function initHeader()
    {
        $this->headerData = [
            'Host' => $this->url,
            'Accept' => '*/*',
            'Authorization' => 'OAuth ' . $this->token,
        ];
    }

    public function getResponse($resource)
    {
        $this->url .= '?ids=' .$this->counterId. '&lang=' .$this->lang. '&' .$resource;
        return $this->sendRequest($this->url);
    }
    
    public function sendRequest($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        foreach ($this->headerData as $name => $value) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("$name: $value" ));
        } 
        curl_setopt($ch,CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \RuntimeException(curl_error($ch));
        }
        curl_close($ch);
        return $data;
    }
}