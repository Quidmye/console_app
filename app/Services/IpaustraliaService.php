<?php

namespace app\Services;

use app\Exceptions\ParserException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\TransferStats;
use PHPHtmlParser\Dom;

class IpaustraliaService{

    private $dom;
    private $client;
    private $token = null;
    private $pageUrl;
    private $trademark;

    public function __construct(Client $client, Dom $dom)
    {
        $this->client = $client;
        $this->dom = $dom;
    }

    /*
     * Парсинг результатов поиска
     */
    public function getRecords($trademark, $page = null){
        $this->trademark = $trademark;
        if(is_null($this->pageUrl)){
            $this->getSearchUrl();
        }
        $i = is_null($page) ? 0 : intval($page);
        $results = [];
        do{
            $url = $this->pageUrl . '&p=' . $i;
            $this->dom->loadFromUrl($url);
            $last = $this->dom->getElementsByClass('goto-last-page');
            $lastPage = is_null($page) && !is_null($last[0]) ? intval($last[0]->getAttribute('data-gotopage')) : $i;
            $records = $this->dom->getElementsByClass('mark-line');
            foreach ($records as $record){
                $recordDom = new Dom();
                $recordDom->loadStr($record->innerHtml);
                $img = $recordDom->find('img')[0];
                $status = is_null($recordDom->find('.status div span')[0]) ? [null, null] : explode(':', $recordDom->find('.status div span')[0]->text);
                $results[] = [
                    'number' => $recordDom->find('.qa-tm-number')[0]->text,
                    'logo_url' => is_null($img) ? $img : $img->getAttribute('src'),
                    'name' => trim($recordDom->find('.trademark')[0]->text),
                    'classes' => trim($recordDom->find('.classes')[0]->text),
                    'status1' => empty($status[0]) ? null : trim($status[0]),
                    'status2' => count($status) > 1 ? trim($status[1]) : null,
                    'details_page_url' => 'https://search.ipaustralia.gov.au' . explode('?', $recordDom->find('a.number')[0]->getAttribute('href'))[0]
                ];
            }
            $i++;
        }while($i <= $lastPage);
        return $results;
    }

    /*
     * Получение ссылки с результатами поиска
     */
    private function getSearchUrl(){
        if(is_null($this->token)){
            $this->getToken();
        }
        $cookieJar = CookieJar::fromArray([
            'XSRF-TOKEN' => $this->token
        ], 'search.ipaustralia.gov.au');
        $url = null;
        $this->client->request('POST', 'https://search.ipaustralia.gov.au/trademarks/search/doSearch', [
            'form_params' => [
                '_csrf' => $this->token,
                'wv[0]' => $this->trademark
            ],
            'cookies' => $cookieJar,
            'on_stats' => function (TransferStats $stats) use (&$url) {
                $url = $stats->getEffectiveUri();
            }
        ]);
        $this->pageUrl = $url;
    }


    /*
     * Получение CSRF со страницы
     */
    private function getToken(){
        $this->dom->loadFromUrl('https://search.ipaustralia.gov.au/trademarks/search/advanced');
        foreach ($this->dom->find('meta') as $meta){
            if($meta->getAttribute('name') === '_csrf'){
                $this->token = $meta->getAttribute('content');
            }
        }
        if(is_null($this->token)){
            throw new ParserException('Token Not Found');
        }
    }

}
