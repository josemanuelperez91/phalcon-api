<?php

use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use Phalcon\Loader;
use Phalcon\Mvc\Micro;

$loader = new Loader();
$loader->registerNamespaces(
    [
        'PhalconAPI\Models' => __DIR__ . '/models/',
    ]
);
$loader->register();

$container = new FactoryDefault();
$container->set(
    'db',
    function () {
        return new PdoMysql(
            [
                'host' => 'localhost',
                'username' => 'root',
                'password' => '1812',
                'dbname' => 'market',
            ]
        );
    }
);

$app = new Micro($container);

function toIndexArray($array, $key)
{

    $indexedArray = [];

    foreach ($array as $value) {
        $indexedArray[$value[$key]] = $value;
    }

    return $indexedArray;
}

$app->get('/api/http', function () {

    try {

        $url = 'https://gdh2webfgapi.webfg.com:8088/v1/gdh/marketdata/sab/?code_security=1829035&solution_symbol=SABWEB';
        $url2 = 'https://gdh2webfgapi.webfg.com:8088/v1/gdh/codeconverter/sab/?code_security=1829035&solution_symbol=SABWEB';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_URL, $url);
        $marketDataResponse = json_decode(curl_exec($ch), true);

        curl_setopt($ch, CURLOPT_URL, $url2);
        $codeConverterResponse = json_decode(curl_exec($ch), true);

        $errmsg = curl_error($ch);

        if ($errmsg) {
            throw new \Exception(
                $errmsg
            );
        }
        curl_close($ch);

        $marketData = $marketDataResponse['records'];
        $codeConverter = $codeConverterResponse['records'];

        $marketDataIndexed = toIndexArray($marketData, 'codeSecurity');
        $codeConverterIndexed = toIndexArray($codeConverter, 'codeSecurity');

        $final_result = [];

        foreach ($marketDataIndexed as $key => $value) {
            $codeKey = $codeConverterIndexed[$key]["codeKey"];
            $final_result[] = array(
                "codeSecurity" => $key,
                "nameAsset" => $value["nameAsset"],
                "trade" => $value["trade"], "codeKey" => $codeKey);
        }

        $response = new Response();
        $response->setJsonContent(
            $final_result
        );
        return $response;

    } catch (Exception $exception) {
        $response = new Response();
        $response->setJsonContent(
            [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]
        );
        return $response;
    }
}
);
$app->get('/api/mysql', function () use ($app) {
    $phql = 'SELECT NameCompany,Country,Instrument,Bid,Ask,Yield,High,Low,Currency,DatePrice,TimePrice '
        . 'FROM PhalconAPI\Models\CompanySecurity CS '
        . 'JOIN PhalconAPI\Models\Company C ON CS.CodeCompany = C.CodeCompany '
        . 'JOIN PhalconAPI\Models\Security S ON CS.CodeSecurity = S.CodeSecurity ';


    $companies = $app
        ->modelsManager
        ->executeQuery($phql);

    $response = new Response();
    $response->setJsonContent(
        $companies
    );
    return $response;
});

$app->handle(
    $_SERVER["REQUEST_URI"]
);
