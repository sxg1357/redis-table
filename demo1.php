<?php
/**
 * Created by PhpStorm.
 * User: sxg
 * Date: 2023/3/11
 * Time: 16:12
 */
set_error_handler(function (){});
$fp = stream_socket_client("127.0.0.1:9501", $errno, $errstr);
fwrite($fp,"hello");
Swoole\Event::add($fp, function($fp) {
    $resp = fread($fp, 8192);
    echo $resp.PHP_EOL;
    Swoole\Event::del($fp);
    fclose($fp);
});

Swoole\Event::dispatch();
restore_error_handler();