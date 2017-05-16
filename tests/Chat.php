<?php
use Ratchet\Server\IoServer;

use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\WebSocket\WsServer;

require '../vendor/autoload.php';

    


class Pusher implements MessageComponentInterface {

    protected $clients;

    public function __construct($loop) {
        $this->clients = new \SplObjectStorage;
        #$loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);
        $factory->createServer('localhost:5780')->then(function (\React\Datagram\Socket $server) {
            $server->on('message', function($message, $address, $server) {
                $this->onStreamEvent($message);
            });
        });
        #$loop->run();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        #echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {

    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function onStreamEvent($data) {
        #echo "\nsending";
        foreach ($this->clients as $client) {
            $client->send($data);
        }
    }
}


$loop   = React\EventLoop\Factory::create();
$pusher = new \Pusher($loop);

$webSock = new React\Socket\Server($loop);
$webSock->listen(8081, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
$webServer = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            $pusher
        )
    ),
    $webSock
);

$loop->run();
