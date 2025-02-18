<?php

namespace core\Components;

use Auryn\Injector;
use core\Includes\Exceptions\CommandNotFoundException;
use core\Includes\Exceptions\InvalidArgumentException;

class ConsoleRouter
{
    private $params = [];
    private $current = [];
    private $routes = [];
    private $pattenrs = [
        'number' => '[0-9]+',
        'boolean' => '(1|0)'
    ];

    public function __construct()
    {
        $this->loadRoutes();
        $this->parseCommand();
    }

    public function run()
    {
        if (!array_key_exists('action', $this->current)) {
            //throw here;
        } else if (is_callable($this->current['action'])) {
            call_user_func($this->current['action']);
        } else {
            $injector = new Injector();
            $action = explode('@', $this->current['action']);
            $arguments = [];
            foreach ($this->params as $key => $value) {
                $arguments[':' . $key] = $value;
            }
            $injector->execute([$action[0], $action[1]], $arguments);
        }
    }

    public function getParam($param, $alter = null)
    {
        if (array_key_exists($param, $this->params)) {
            return $this->params[$param];
        }
        return $alter;
    }

    private function loadRoutes()
    {
        $routes = include ROOT_DIR . '/app/Routes/console.php';
        foreach ($routes as $route => $options) {
            $route = preg_replace('!\s++!u', ' ', $route);
            $parts = explode(' ', $route);
            $key = array_splice($parts, 0, 1)[0];
            $arguments = [];
            foreach ($parts as $part) {
                $argument = preg_replace('/^({)?([-]{1,2})?([a-zA-Z]+)(})?$/', '$3', $part);
                $arguments[$argument] = [
                    'optional' => preg_match('/^{.*}$/', $part) !== 0
                ];
            }
            $this->routes[$key] = array_merge($options, ['arguments' => $arguments]);
        }
    }

    private function parseCommand()
    {
        if (array_key_exists(1, $_SERVER['argv'])) {
            $command = $_SERVER['argv'][1];
        } else {
            $command = Config::get('app.home');
        }
        if (!array_key_exists($command, $this->routes)) {
            throw  new CommandNotFoundException('Invalid command');
        }
        foreach ($_SERVER["argv"] as $param) {
            if (substr($param, 0, 2) === '--') {
                $argument = $this->parseParam($param);
                if (array_key_exists('where', $this->routes[$command]) && array_key_exists($argument[0], $this->routes[$command]['where'])) {
                    $pattern = array_key_exists($this->routes[$command]['where'][$argument[0]], $this->pattenrs) ? $this->pattenrs[$this->routes[$command]['where'][$argument[0]]] : $this->routes[$command]['where'][$argument[0]];
                    if (!preg_match('/^' . $pattern . '$/', $argument[1])) {
                        throw new InvalidArgumentException(sprintf('%s must be a %s', $argument[0], $pattern));
                    }
                }
                $this->params[$argument[0]] = $argument[1];
            }

        }
        $this->current = $this->routes[$command];
        if (array_key_exists('arguments', $this->current)) {
            foreach ($this->current['arguments'] as $key => $argument) {
                if (!$argument['optional'] && !array_key_exists($key, $this->params)) {
                    throw new InvalidArgumentException(sprintf('%s argument required', $key));
                }
            }
        }
    }

    private function parseParam($param)
    {
        $valuesMap = [
            'true' => true,
            'false' => false,
            'null' => null,
            '' => null
        ];
        $key = preg_replace('/^--([^=]+)=(.*)$/', '$1', $param);
        $value = preg_replace('/^--([^=]+)=(.*)$/', '$2', $param);
        if (array_key_exists($value, $valuesMap)) {
            $value = $valuesMap[$value];
        }
        return [$key, $value];
    }
}