<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/20
 * Time: 16:20
 */

$server = new Swoole\Server('0.0.0.0', 9502);
$table = new Swoole\Table(128);
$table->column('data', Swoole\Table::TYPE_STRING, 1024);
$table->create();
$server->table = $table;

$server->on('start', function ($server) {
    echo "TCP Server is started at tcp://127.0.0.1:9502\n";
});

$process = new Swoole\Process(function () use ($server) {
    $fp = stream_socket_client("tcp://127.0.0.1:9501", $error_code, $error_message, 30);
    fwrite($fp, json_encode(['action' => 'replication']));

    Swoole\Event::add($fp,function($fp) use ($server) {
        $data = fread($fp, 1024);
        print_r(json_decode($data, true));
        if ($data == '') {
            Swoole\Event::del($fp);
            fclose($fp);
        } else {
            $data = json_decode($data, true);
            if (isset($data['key'])) {
                $server->table->set($data['key'], ['data' => $data['val']]);
            }
        }
    });
    Swoole\Event::wait();
}, false, 2, 1);

$server->addProcess($process);

$server->on('connect', function ($server, $fd) {

});

$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    $data = json_decode($data, true);
    if (isset($data['key'])) {
        $val = $server->table->get($data['key']);
        $server->send($fd, json_encode(['code' => '200', 'val' => $val['data']]));
    } else {
        $server->send($fd, json_encode(['code' => '404', 'msg' => 'not found']));
    }
});

$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});


$server->start();