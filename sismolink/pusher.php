<?php

namespace sismolink;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Pusher implements MessageComponentInterface {

    protected $clients;

    public function __construct($loop, $ip, $port) {
        $this->clients = new \SplObjectStorage;
        $factory = new \React\Datagram\Factory($loop);
        $factory->createServer('localhost:5780')->then(function (\React\Datagram\Socket $server) {
            $server->on('message', function($message, $address, $server) {
                $this->onStreamEvent($message);
            });
        });
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
