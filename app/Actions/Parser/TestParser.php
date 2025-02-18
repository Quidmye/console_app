<?php

namespace app\Actions\Parser;

use app\Services\IpaustraliaService;
use core\Components\ConsoleRouter;

class TestParser
{

    public function parse(IpaustraliaService $service, ConsoleRouter $router, $trademark)
    {
        $page = $router->getParam('page');
        try {
            $records = $service->getRecords($trademark, $page);
        } catch (\Exception $exception) {
            print "\033[31m" . $exception->getMessage() . "\033[0m";
            return;
        }
        print json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function help()
    {
        print "USE COMMAND  \033[31m php console.php list\033[0m";
        print PHP_EOL . 'PARAMS:';
        print PHP_EOL . '--trademark="{QUERY}" ' . "\033[31m REQUIRED \033[0m";;
        print PHP_EOL . '--page={PAGE} ' . "\033[31m OPTIONAL \033[0m";;
    }

}