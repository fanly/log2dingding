<?php
/**
 * Created by PhpStorm.
 * User: yemeishu
 * Date: 2018/2/20
 * Time: 下午4:46
 */

namespace Fanly\Log2dingding\Support;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class Client {
    public function httpPostJson(string $accesstoken, array $data = []) {
        $client = new GuzzleClient();
        $promise = $client->requestAsync('POST',
            'https://oapi.dingtalk.com/robot/send',
            [
                'query' => ['access_token' => $accesstoken],
                'headers' => [
                    'Accept' => 'application/json'
                ],
                'json' => $data
            ]);
        $promise->then(
            function (ResponseInterface $res) {
            },
            function (RequestException $e) {
            }
        );
        $promise->wait();
    }
}