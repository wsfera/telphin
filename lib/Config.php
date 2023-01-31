<?php
namespace Telphin;

use Telphin\Exceptions\FileNotFoundException;

/**
 *
 */
class Config
{

    private $confPath = __DIR__ . "/../config.json";
    private $data;

    function __construct()
    {
        $existConf = file_exists($this->confPath);
        if ($existConf){
            $this->data = json_decode(
                \file_get_contents($this->confPath),
                true
            );
        } else {
            throw new FileNotFoundException("Не удалось получить конфигурационный файл по пути {$this->confPath}", 1);
        }

    }

    public function conf(string $path)
    {
        $confData = $this->data;
        if (empty($confData)) throw new FileNotFoundException("Отсутствуют данные в конфигурационном файле по пути {$this->confPath}", 2);
        $arr = explode('.', $path);
        foreach ($arr as $value) {
            if (array_key_exists($value, $confData)) {
                $confData = $confData[$value];
                continue;
            } else {
                return false;
            }
        }
        return $confData;
    }

    public function saveConf()
    {
        return file_put_contents($this->confPath, json_encode($this->data));
    }

    public function setToken($token)
    {
        $this->data['token'] = $token;
        return $this;
    }
}
