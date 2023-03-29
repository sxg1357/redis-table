<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/28
 * Time: 14:31
 */

$server = new Swoole\Server('127.0.0.1', 9503);

$server->on('start', function ($server) {
    echo "TCP Server is started at tcp://127.0.0.1:9503\n";
});
$server->set(['worker_num' => '1']);

$process = new Swoole\Process(function ($process) use ($server) {
    $socket = $process->exportSocket();
    $masterFd = stream_socket_client("tcp://127.0.0.1:9501", $error_code, $error_message, 30);
    $slaveFd = stream_socket_client("tcp://127.0.0.1:9502", $error_code, $error_message, 30);
    $fd = stream_socket_client("tcp://127.0.0.1:9503", $error_code, $error_message, 30);
    Swoole\Event::add($socket, function ($socket) use ($masterFd, $slaveFd) {
        /**@var \Swoole\Coroutine\Socket $socket*/
        $data = json_decode($socket->recv(1024), true);
        print_r($data);
        if ($data['action'] == 'get') {
            $fds = [$masterFd, $slaveFd];
            $num = mt_rand(0, 1);
            echo "num:$num".PHP_EOL;
            fwrite($fds[$num], json_encode($data));
        } else if ($data['action'] == 'set') {
            fwrite($masterFd, json_encode($data));
        }
    });

    Swoole\Event::add($masterFd, function ($masterFd) use ($fd) {
        $data = fread($masterFd, 1024);
        if ($data == '') {
            Swoole\Event::del($masterFd);
            fclose($masterFd);
        }
        Swoole\Event::add($fd, function() {}, function ($fd) use ($data) {
            fwrite($fd, $data);
            Swoole\Event::del($fd);
        }, SWOOLE_EVENT_WRITE);
    });

    Swoole\Event::add($slaveFd, function ($slaveFd) use ($fd) {
        $data = fread($slaveFd, 1024);
        if ($data == '') {
            Swoole\Event::del($slaveFd);
            fclose($slaveFd);
        }
        Swoole\Event::add($fd, function() {}, function ($fd) use ($data) {
            fwrite($fd, $data);
            Swoole\Event::del($fd);
        }, SWOOLE_EVENT_WRITE);

    });
    Swoole\Event::wait();
}, false, 2, 1);

$server->addProcess($process);

$server->on('connect', function ($server, $fd){
    echo "connection open: {$fd}\n";
});

$server->on('receive', function ($server, $fd, $reactor_id, $data) use ($process) {
    /**@var \Swoole\Coroutine\Socket $socket*/
    $socket = $process->exportSocket();
    $data = json_decode($data, true);
    $action = $data['action'];
    switch ($action) {
        case 'get':
        case 'set':
            $data['fdx'] = $fd;
            $socket->send(json_encode($data));
            break;
        case 'response':
            $fdx = $data['fdx'];
            $server->send($fdx, json_encode(['code' => $data['code'], 'val' => $data['val']]));
            break;
        default:
            break;
    }
});

$server->on('close', function ($server, $fd) {
    echo "connection close: {$fd}\n";
});

$server->start();
