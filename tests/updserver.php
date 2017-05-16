<?php
require_once '../vendor/autoload.php';
$loop = React\EventLoop\Factory::create();
$factory = new React\Datagram\Factory($loop);
$factory->createServer('192.168.40.125:5678')->then(function (React\Datagram\Socket $server) {
    echo "started Server\n";
    $server->on('message', function($message, $address, $server) {
        #$server->send('hello ' . $address . '! echo: ' . $message, $address);
        echo 'client ' . $address . ': ' . $message . PHP_EOL;
    });
});
$loop->run();
