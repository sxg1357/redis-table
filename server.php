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

$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    print_r($data);
});

$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();