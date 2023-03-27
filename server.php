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
        switch ($action) {
            case "set":
                $key = $data['key'];
                $val = $data['val'];
                $connectionData[$key] = $val;
                foreach ($replicationFd as $fdx) {
                    $server->send($fdx, json_encode(['key' => $key, 'val' => $val]));
                }
                break;
            case "get":
                $key = $data['key'];
                if (isset($connectionData[$key]) && $connectionData[$key]) {
                    $server->send($fd, json_encode(['code' => 200, 'val' => $connectionData[$key]]));
                } else {
                    $server->send($fd, json_encode(['code' => '404', 'msg' => 'not found']));
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