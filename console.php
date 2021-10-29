<?php

define('ROOT_DIR', dirname(__FILE__));

require_once './vendor/autoload.php';

use core\Components\ConsoleRouter;
use core\Includes\Exceptions\CommandNotFoundException;
use \core\Includes\Exceptions\InvalidArgumentException;

try {
    (new ConsoleRouter())->run();
} catch (InvalidArgumentException $exception) {
    print "\033[31m" . $exception->getMessage() . "\033[0m";
} catch (CommandNotFoundException $exception) {
    print "\033[31m" . $exception->getMessage() . "\033[0m";
}
