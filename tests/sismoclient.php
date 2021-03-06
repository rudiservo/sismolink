<?php
require 'vendor/autoload.php';


$loop = React\EventLoop\Factory::create();
$factory = new React\Dns\Resolver\Factory();
$resolver = $factory->createCached('8.8.8.8', $loop);
$factory = new React\Datagram\Factory($loop, $resolver);
addReactClient('192.168.2.100:5678', $factory, $loop);
addReactClient('192.168.2.100:5678', $factory, $loop);
$loop->run();

function addReactClient($address, $factory, $loop)
{
    $factory->createClient($address)->then(function (React\Datagram\Socket $client) use ($loop) {
        $client->send(rand(0,500));
        /*$client->on('message', function($message, $serverAddress, $client) {
            echo 'received "' . $message . '" from ' . $serverAddress. PHP_EOL;
        });
        $client->on('error', function($error, $client) {
            echo 'error: ' . $error->getMessage() . PHP_EOL;
        });*/
        $tid = $loop->addPeriodicTimer(0.0156, function() use ($client, &$n) {
            $client->send(rand(0,500));
        });
        // read input from STDIN and forward everything to server
        $loop->addReadStream(STDIN, function () use ($client, $loop, $tid) {
            $msg = fgets(STDIN, 2000);
            if ($msg === false) {
                // EOF => flush client and stop perodic sending and waiting for input
                $client->end();
                $loop->cancelTimer($tid);
                $loop->removeReadStream(STDIN);
            } else {
                $client->send(trim($msg));
            }
        });
    }, function($error) {
        echo 'ERROR: ' . $error->getMessage() . PHP_EOL;
    });
}
