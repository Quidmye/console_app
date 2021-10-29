<?php

namespace app\Actions\Parser;

use app\Services\IpaustraliaService;
use core\Components\ConsoleRouter;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\TransferStats;
use PHPHtmlParser\Dom;
use GuzzleHttp\Client;

class TestParser{

    public function parse(IpaustraliaService $service, ConsoleRouter $router, $trademark){
        $page = $router->getParam('page');
        try {
            $records = $service->getRecords($trademark, $page);
        }catch (\Exception $exception){
            print "\033[31m" . $exception->getMessage() . "\033[0m";
            return;
        }
        print json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function help(){
        print "USE COMMAND  \033[31m php console.php list\033[0m";
        print PHP_EOL . 'PARAMS:';
        print PHP_EOL . '--trademark="{QUERY}" ' . "\033[31m REQUIRED \033[0m";;
        print PHP_EOL . '--page={PAGE} ' . "\033[31m OPTIONAL \033[0m";;
    }

    protected function show_status($done, $total, $size=30) {

        static $start_time;

        // if we go over our bound, just ignore it
        if($done > $total) return;

        if(empty($start_time)) $start_time=time();
        $now = time();

        $perc=(double)($done/$total);

        $bar=floor($perc*$size);

        $status_bar="\r[";
        $status_bar.=str_repeat("=", $bar);
        if($bar<$size){
            $status_bar.=">";
            $status_bar.=str_repeat(" ", $size-$bar);
        } else {
            $status_bar.="=";
        }

        $disp=number_format($perc*100, 0);

        $status_bar.="] $disp%  $done/$total";

        $rate = ($now-$start_time)/$done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $start_time;

        $status_bar.= " remaining: ".number_format($eta)." sec.  elapsed: ".number_format($elapsed)." sec.";

        echo "$status_bar  ";

        flush();

        // when done, send a newline
        if($done == $total) {
            echo "\n";
        }

    }

}