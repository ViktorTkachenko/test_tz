<?php
/**
 * Created by PhpStorm.
 * User: leps
 * Date: 09.10.2021
 * Time: 20:41
 */

class IpAustralia
{
    private $argc;
    private $argv;
    private $argTradeMarkName;
    private $setCookie;
    private $tradeMarksList = [];

    /**
     * for convert relative url toAbsolute
     * @var string
     */
    private $addPrefix = 'https://search.ipaustralia.gov.au';

    /**
     * url for page with form
     * @var string
     */
    private $formPageUrl = 'https://search.ipaustralia.gov.au/trademarks/search/advanced';

    /**
     * host for headers
     * @var string
     */
    private $hostForHeaders = 'search.ipaustralia.gov.au';

    /**
     * csrf
     * @var
     */
    private $csrf;

    /**
     * trade mark name
     * @var
     */
    private $tradeMarkName;

    /**
     * @var int
     */
    private $maxRedirs = 10;

    /**
     * @var int
     */
    private $timeOut = 30;

    public function __construct($argc, $argv)
    {
        $this->argc = $argc;
        $this->argv = $argv;
    }

    /**
     * clear string
     * @param $string
     * @return mixed|null|string|string[]
     */
    private function clearString($string){
        $string = strip_tags($string);
        $string = str_replace("\xC2\xA0", ' ', $string);
        $string = preg_replace('/\s+/u', ' ', trim($string));
        $string = trim($string);
        return $string;
    }

    /**
     * convert relative url toAbsolute
     * @param $url
     * @param $addPrefix
     * @return string
     */
    private function convertRelativeUrlToAbsolute($url, $addPrefix)
    {
        $url = trim($url);
        if (!empty($url)) {
            $part = parse_url($url);
            if (empty($part['host'])) {
                $url = $addPrefix . $url;
            }
        }
        return $url;
    }

    /**
     * set headers
     * @param $url
     * @param string $post
     * @return string
     */
    private function setHeaders($url, $post='')
    {
        $headers = 'host: ' . $this->hostForHeaders . "\r\n" .
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9' . "\r\n" .
            'cache-control: no-cache' . "\r\n" .
            'pragma: no-cache' . "\r\n" .
            'referer: ' . $url . "\r\n" .
            'sec-ch-ua: "Chromium";v="94", "Google Chrome";v="94", ";Not A Brand";v="99"' . "\r\n" .
            'sec-ch-ua-mobile: ?0' . "\r\n" .
            'sec-fetch-dest: document' . "\r\n" .
            'sec-fetch-mode: navigate' . "\r\n" .
            'sec-fetch-site: same-origin' . "\r\n" .
            'sec-fetch-user: ?1' . "\r\n" .
            'upgrade-insecure-requests: 1' . "\r\n" .
            'user-agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36' . "\r\n";
        if (!empty($post)) {
            $headers .= 'content-length: ' . strlen($post) . "\r\n";
            $headers .= 'content-type: application/x-www-form-urlencoded' . "\r\n";
        }
        if (!empty($this->setCookie)) {
            $headers .= 'Cookie: ' . $this->setCookie . "\r\n";
        }
        return $headers;
    }

    /**
     * get code unswer from request
     * @param $responseHeaders
     * @return string
     */
    private function getCodeResponse($responseHeaders)
    {
        $code = '';
        foreach($responseHeaders as $row){
            if(strripos($row, 'HTTP/') !== false){
                $a = explode(' ', $row);
                $code = (!empty($a[1])) ? trim($a[1]) : '';
            }
        }
        return $code;
    }

    /**
     * parse Cookie from response headers
     * @param $responseHeaders
     * @return string
     */
    private function getCookie($responseHeaders)
    {
        $setCookieString = '';
        $setCookieList = [];
        foreach($responseHeaders as $row){
            if(strripos($row, 'Set-Cookie:') !== false){
                $a = explode('Set-Cookie:', $row);
                $setCookieList[] = (!empty($a[1])) ? trim($a[1]) : '';
            }
        }
        if (!empty($setCookieList)) {
            $setCookieString = implode('; ', $setCookieList);
        }
        return $setCookieString;
    }

    /**
     * get request
     * @param $data
     * @param $metod
     * @return array
     */
    private function httpRequest($data, $metod)
    {
        $response = [
            'page'=>'',
            'http_code'=>'',
        ];
        // create stream
        $opts = [
            'http'=>[
                'method'=>$metod,
                'ignore_errors'=>true,
                'timeout'=>$this->timeOut,
                'max_redirects'=>$this->maxRedirs,
                'header'=>$data['headers'],
            ],
            "ssl"=>[
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
        ];
        if (!empty($data['post'])) {
            $opts['http']['content'] = $data['post'];
        }

        $context = stream_context_create($opts);
        $response['page'] = file_get_contents($data['url'], false, $context);
        $response['http_code'] = $this->getCodeResponse($http_response_header);
        // parse cookie from response headers
        $cookie = $this->getCookie($http_response_header);
        // set cookie
        $this->setCookie($cookie);

        return $response;
    }

    /**
     * validation param
     * @throws Exception
     */
    private function validationParam()
    {
        if ($this->argc != 2 || in_array($this->argv[1], array('--help', '-help', '-h', '-?'))) {
            $test = '';
            $test .=  'This is a console PHP script that takes one argument.' . "\n";
            $test .=  'Usage: ' . $this->argv[0] . ' <option>' . "\n";
            $test .=  '<option> Trade mark name' . "\n";
            $test .=  '<option> --help, -help, -h, or -? will show the current help information.' . "\n";
            throw new Exception($test);
        }
        $this->argTradeMarkName = $this->argv[1];
    }

    /**
     * get page with form
     * @param $url
     * @return mixed
     * @throws Exception
     */
    private function getPage($url)
    {
        $data = [
            'url'=>$url,
            'headers'=>$this->setHeaders($url),
        ];
        $response = $this->httpRequest($data, 'GET');
        if ($response['http_code'] != '200') {
            $textError = 'Error: not get page [' . $url . '] http_code [' . $response['http_code'] . ']';
            throw new Exception($textError);
        }
        return $response['page'];
    }

    /**
     * get _csrf from page
     * @param $page
     * @return array|null|phpQueryObject|string
     * @throws Exception
     */
    private function getCSRF($page)
    {
        $page = phpQuery::newDocument($page);
        $csrf = $page->find('form#basicSearchForm input[name="_csrf"]')->attr('value');
        phpQuery::unloadDocuments($page);
        if (empty($csrf)) {
            throw new Exception('Error: not get csrf');
        }
        return $csrf;
    }

    /**
     * set _csrf field
     * @param $csrf
     */
    private function setCSRF($csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * set trade mark name field
     * @param $tradeMarkName
     */
    private function setTradeMarkName($tradeMarkName)
    {
        $this->tradeMarkName = $tradeMarkName;
    }

    /**
     * set cookie
     * @param $cookie
     */
    private function setCookie($cookie)
    {
        $this->setCookie = $cookie;
    }

    /**
     * send form and get response
     * @return mixed
     * @throws Exception
     */
    private function sendForm()
    {
        $formFieldsList = [
            '_csrf'=>$this->csrf,
            'wv[0]'=>$this->tradeMarkName,
            'wt[0]'=>'PART',
            'weOp[0]'=>'AND',
            'wv[1]'=>'',
            'wt[1]'=>'PART',
            'wrOp'=>'AND',
            'wv[2]'=>'',
            'wt[2]'=>'PART',
            'weOp[1]'=>'AND',
            'wv[3]'=>'',
            'wt[3]'=>'PART',
            'iv[0]'=>'',
            'it[0]'=>'PART',
            'ieOp[0]'=>'AND',
            'iv[1]'=>'',
            'it[1]'=>'PART',
            'irOp'=>'AND',
            'iv[2]'=>'',
            'it[2]'=>'PART',
            'ieOp[1]'=>'AND',
            'iv[3]'=>'',
            'it[3]'=>'PART',
            'wp'=>'',
            '_sw'=>'on',
            'classList'=>'',
            'ct'=>'A',
            'status'=>'',
            'dateType'=>'LODGEMENT_DATE',
            'fromDate'=>'',
            'toDate'=>'',
            'ia'=>'',
            'gsd'=>'',
            'endo'=>'',
            'nameField[0]'=>'OWNER',
            'name[0]'=>'',
            'attorney'=>'',
            'oAcn'=>'',
            'idList'=>'',
            'ir'=>'',
            'publicationFromDate'=>'',
            'publicationToDate'=>'',
            'i'=>'',
            'c'=>'',
            'originalSegment'=>'',
        ];
        $url = 'https://search.ipaustralia.gov.au/trademarks/search/doSearch';
        $post = http_build_query($formFieldsList);
        $data = [
            'url'=>$url,
            'headers'=>$this->setHeaders($url, $post),
            'post'=>$post,
        ];
        $response = $this->httpRequest($data, 'POST');
        if ($response['http_code'] != '200') {
            $textError = 'Error: not send form [' . $url . '] code [' . $response['http_code'] . ']';
            throw new Exception($textError);
        }
        return $response['page'];
    }

    /**
     * get page result
     * @param $pageForm
     * @return mixed
     * @throws Exception
     */
    private function getPageResult($pageForm)
    {
        // get _csrf from page
        $csrf = $this->getCSRF($pageForm);
        // set _csrf
        $this->setCSRF($csrf);
        // set trade mark name
        $this->setTradeMarkName($this->argTradeMarkName);
        // send form and get result page
        $pageResult = $this->sendForm();
        return $pageResult;
    }

    /**
     * parse trades from page
     * @param $page
     */
    private function parseTradeMarksFromPage($page)
    {
        $page = phpQuery::newDocument($page);
        foreach($page->find('table#resultsTable tr.mark-line.result') as $tr){
            $number = $this->clearString(pq($tr)->find('td.number')->html());
            if (empty($number)) {
                continue;
            }

            $pageUrl = pq($tr)->find('td.number a')->attr('href');
            pq($tr)->find('i.status')->remove();
            $status = $this->clearString(pq($tr)->find('td.status')->html());

            $this->tradeMarksList[$number] = [
                'number'=>$number,
                'logo_url'=>pq($tr)->find('td.trademark.image img')->attr('src'),
                'name'=>$this->clearString(pq($tr)->find('td.trademark.words')->html()),
                'classes'=>$this->clearString(pq($tr)->find('td.classes')->html()),
                'status'=>$status,
                'details_page_url'=>$this->convertRelativeUrlToAbsolute($pageUrl, $this->addPrefix),
            ];
        }
    }

    /**
     * get next pagin page url
     * @param $page
     * @return array|null|phpQueryObject|string
     */
    private function getNextPaginPageUrl($page)
    {
        $page = phpQuery::newDocument($page);
        $nextPageUrl = $page->find('div.pagination-container:eq(0) div.pagination-buttons a.js-nav-next-page.goto-page')->attr('href');
        if (!empty($nextPageUrl)) {
            $nextPageUrl = $this->convertRelativeUrlToAbsolute($nextPageUrl, $this->addPrefix);
        }
        return $nextPageUrl;
    }

    /**
     * parse trades from pagin pages
     * @param $pageResult
     * @throws Exception
     */
    private function parseTradeMarksFromPaginPages($pageResult)
    {
        // get next pagin page url
        $nextPage = $this->getNextPaginPageUrl($pageResult);
        while(true){
            if (empty($nextPage)) {
                break;
            }
            sleep(1);
            $pagePagin = $this->getPage($nextPage);
            // parse trades from page pagin
            $this->parseTradeMarksFromPage($pagePagin);
            // get next pagin page url
            $nextPage = $this->getNextPaginPageUrl($pagePagin);
        }
    }

    /**
     * @throws Exception
     */
    private function getListTradeMarks()
    {
        // get page with form fields
        $pageForm = $this->getPage($this->formPageUrl);
        // get page result with list trade marks data
        $pageResult = $this->getPageResult($pageForm);
        // parse trades from page result
        $this->parseTradeMarksFromPage($pageResult);
        // parse trades from pagin pages
        $this->parseTradeMarksFromPaginPages($pageResult);
    }

    /**
     * parse and print result
     */
    public function printTradeMarksList()
    {
        try {
            // validation param
            $this->validationParam();
            // get trade marks list data
            $this->getListTradeMarks();
            // print list data
            $this->tradeMarksList = array_values($this->tradeMarksList);
            array_unshift($this->tradeMarksList, NULL);
            unset($this->tradeMarksList[0]);
            print_r($this->tradeMarksList);
            echo 'Total result:' . count($this->tradeMarksList) . "\n";
        } catch (Exception $e) {
            echo  $e->getMessage();
        }
    }
}