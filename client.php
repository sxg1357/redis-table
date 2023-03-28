<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/20
 * Time: 15:29
 */

$fp = stream_socket_client("tcp://127.0.0.1:9503", $error_code, $error_message, 30);

Swoole\Event::add($fp, function ($fp) {
    $data = fread($fp, 1024);
    print_r(json_decode($data, true));
});

Swoole\Event::add(STDIN, function ($fd) use ($fp) {
    $data = fgets($fd, 1024);
    if ($data) {
        $data = explode(" ", $data);
        $key = trim($data[1]);
        if (in_array($data[0], ['get', 'set'])) {
            $send = ['action' => $data[0], 'key' => $key];
            if ($data[0] == 'set') {
                $val = trim($data[2]);
                $send['val'] = $val;
            }
            fwrite($fp, json_encode($send));
        }
    }
});

Swoole\Event::wait();