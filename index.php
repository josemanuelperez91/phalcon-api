<?php

use Phalcon\Http\Response;
use Phalcon\Mvc\Micro;

$app = new Micro();

$app->get('/api/http', function () {

    $url = 'https://gdh2webfgapi.webfg.com:8088/v1/gdh/marketdata/sab/?code_security=1829035&solution_symbol=SABWEB';
    $url2 = 'https://gdh2webfgapi.webfg.com:8088/v1/gdh/codeconverter/sab/?code_security=1829035&solution_symbol=SABWEB';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_URL, $url);
    $result = json_decode(curl_exec($ch), true);

    curl_setopt($ch, CURLOPT_URL, $url2);
    $result2 = json_decode(curl_exec($ch), true);

    // $final_result = array();

    $final_result = array_merge_recursive($result2['records'], $result['records']);
    foreach ($result['records'] as $record) {

        // $codeKey = array_search($result2['records'],)
        // array_push($final_result, array('codeSecurity' => $record['codeSecurity'],
        //     'nameAsset' => $record['nameAsset'],
        //     'trade' => $record['trade'],
        //     'codeKey' => $result2['records'][$record['codeSecurity']]['codeKey']
        // ));

    }

    $errmsg = curl_error($ch);

    curl_close($ch);
    $response = new Response();

    $response->setJsonContent(
        $final_result
    );
    return $response;
}
);
$app->get(
    '/',
    function () {
        echo '';
    }
);

$app->handle(
    $_SERVER["REQUEST_URI"]
);
