<?php

namespace core\Components;

use BadMethodCallException;

class Config
{

    private $config = [];
    private static $instance = null;

    public function __construct()
    {
        $fileList = scandir(ROOT_DIR . '/app/Config/');
        foreach ($fileList as $filename) {
            if (is_file(ROOT_DIR . '/app/Config/' . $filename)) {
                $config = require_once ROOT_DIR . '/app/Config/' . $filename;
                $name = str_replace('.php', '', $filename);
                $this->config = array_merge($this->config, [$name => $config]);
            }
        }
    }

    public static function __callStatic($method, $arguments)
    {
        if ($method === "get") {
            return self::getInstance()->gets($arguments[0]);
        }
        forward_static_call(array(self::class, $method), $arguments);
    }

    public function gets($key)
    {
        $parsedKey = explode('.', $key);
        $config = $this->config;
        foreach ($parsedKey as $item) {
            if (!array_key_exists($item, $config)) {
                return null;
            }
            $config = $config[$item];
        }
        return $config;
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

}