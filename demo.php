<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/11
 * Time: 16:17
 */

set_error_handler(function () {});
$fd = stream_socket_server('tcp://0.0.0.0:9501', $errno, $error_message);
function readData($fd) {
    $data = fread($fd, 1024);
    if ($data == '') {
        Swoole\Event::del($fd);
        fclose($fd);
    } else {
        fprintf(STDOUT, 'recv data from client, data:%s', $data);
        fwrite($fd, "hello world\r\n");
    }
}

Swoole\Event::add($fd, function ($fd) {
    $connection = stream_socket_accept($fd);
    if (is_resource($connection)) {
        Swoole\Event::add($connection, 'readData');
    }
});

Swoole\Event::dispatch();
restore_error_handler();