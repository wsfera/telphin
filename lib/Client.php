<?php

namespace Telphin;

use Telphin\Config;
use Telphin\Methods;

/**
 * Connection with api telphin
 * Methods - traits
 */
class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PUT = 'PUT';
    const GRANT_TYPE = 'client_credentials';
    public $type = 'client';
    protected $client_id;
    protected $client_secret;
    protected $conf;
    protected $url;
    protected static $version;
    protected $token;

    public function __construct($client_id, $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->conf = (new Config);
        $this->url = $this->conf->conf("url");
        $this->token = $this->conf->conf("token");
        self::$version = $this->conf->conf("versionPrefix");
    }

    protected function refreshToken()
    {
        $path = "/oauth/token";
        $method = "POST";
        $data = [
            "grant_type" => self::GRANT_TYPE,
            "client_secret" => $this->client_secret,
            "client_id" => $this->client_id
        ];
        return $this->makeRequest($path, $method, $data)->access_token;
    }

    protected function makeRequest(string $pathInput, string $method, array $dataInput = [], $encode = false)
    {
        $curlHandler = curl_init();
        $path = $pathInput;
        if (self::METHOD_GET == $method && count($dataInput)) {
            $path=  $pathInput."?" . http_build_query($dataInput, '', '&');
        }
        $data = ($encode)? json_encode($dataInput) : $dataInput;
        curl_setopt($curlHandler, CURLOPT_URL, $this->url . $path);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandler, CURLOPT_FAILONERROR, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, $this->conf->conf("timeout"));
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, $this->conf->conf("timeout"));
        switch ($method) {
            case self::METHOD_POST:
                curl_setopt($curlHandler, CURLOPT_POST, 1);
                curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_DELETE:
                curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
                break;
            case self::METHOD_PUT:
                curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $data);
                break;
        }

        if ($path != "/oauth/token" && !$this->token) {
            $token = $this->refreshToken();
            if ($token != 401)
            {
                $this->token = $token;
                $this->conf->setToken($token)->saveConf();
            } else {
                throw new \OAuthException("Неудается авторизоваться, проверьте логин и пароль ");
            }
        }
        if ($path != "/oauth/token" && $this->token) {
            curl_setopt($curlHandler, CURLOPT_HEADER, 0);
            curl_setopt($curlHandler, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "authorization: Bearer {$this->token}"
            ]);
        }

        $result = curl_exec($curlHandler);
        $codeResponse = curl_getinfo($curlHandler, CURLINFO_RESPONSE_CODE);
        curl_close($curlHandler);
        if ($codeResponse == "404") return (object)$this->conf->conf("errors.response404");
        if ($codeResponse == "401" || !$this->token) {
            $this->token = "";
            return $this->makeRequest($pathInput, $method, $dataInput, $encode);
        }
        $result = json_decode($result);
        return $result;
    }

    use Methods\Client\Agent;
    use Methods\Client\Client;
    use Methods\Extension\Extension;
}

