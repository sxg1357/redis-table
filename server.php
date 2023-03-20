<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/20
 * Time: 15:26
 */


$server = new Swoole\Server('127.0.0.1', 9501);

$server->on('start', function ($server) {
    echo "TCP Server is started at tcp://127.0.0.1:9501\n";
});

$server->on('connect', function ($server, $fd){

});

static $connectionData = [];

$server->on('receive', function ($server, $fd, $reactor_id, $data) use (&$connectionData) {
    $data = json_decode($data, true);
    $action = $data['action'];
    $key = rtrim($data["key"]);
    if ($action == "set") {
        $val = rtrim($data["val"]);
        $connectionData[$key] = $val;
    } else if ($action == "get") {
        if (isset($connectionData[$key]) && $connectionData[$key]) {
            $server->send($fd, json_encode(['code' => 200, 'data' => $connectionData[$key]]));
        } else {
            $server->send($fd, json_encode(['code' => 404, 'data' => 'data not found']));
        }
    }
});

$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();