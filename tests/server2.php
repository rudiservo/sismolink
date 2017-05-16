<?php
error_reporting(E_ALL | E_STRICT);

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_bind($socket, 'localhost', 5780);

$from = 'localhost';
$port = 1234;
while (true) {
    socket_recvfrom($socket, $buf, 12, 0, $from);

    echo "Received $buf from remote address $from and remote port" . PHP_EOL;
}
