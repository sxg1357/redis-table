<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/20
 * Time: 15:26
 */

$server = new Swoole\Server('0.0.0.0', 9501);
$server->set(['worker_num' => '1']);
$server->on('start', function ($server) {
    echo "Server is started at tcp://127.0.0.1:9501\n";
});

$server->on('connect', function ($server, $fd) {

});

$connectionData = [];
$replicationFd = [];
$server->on('receive', function ($server, $fd, $reactor_id, $data) {

    global $replicationFd;
    global $connectionData;

    $data = json_decode($data, true);
    $action = $data['action'];
    if ($action == 'replication') {
        $replicationFd[] = $fd;
        $server->send($fd, json_encode(['code' => 200, 'msg' => 'set ok']));
    } else {
        $key = $data['key'];
        switch ($action) {
            case "set":
                $val = $data['val'];
                $connectionData[$key] = $val;
                foreach ($replicationFd as $fdx) {
                    $server->send($fdx, json_encode(['key' => $key, 'val' => $val]));
                }
                break;
            case "get":
                $fdx = $data['fdx'];
                if (isset($connectionData[$key]) && $connectionData[$key]) {
                    $server->send($fd, json_encode(['action' => 'response', 'code' => 200, 'val' => $connectionData[$key], 'fdx' => $fdx]));
                } else {
                    $server->send($fd, json_encode(['action' => 'response', 'code' => '404', 'val' => 'not found', 'fdx' => $fdx]));
                }
                break;
            default:
                break;
        }
    }

});

$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();