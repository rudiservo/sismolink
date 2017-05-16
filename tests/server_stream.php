<?php
if (array_key_exists(1, $argv) && $argv[1] != '' ) {
    $port = $argv[1];
} else {
    $port = 5780;
}
$socket = stream_socket_server("udp://localhost:" . $port, $errno, $errstr, STREAM_SERVER_BIND);
if (!$socket) {
    die("$errstr ($errno)");
}

do {
    #$pkt = stream_socket_recvfrom($socket, 1, 0, $peer);
    #echo "$peer\n";
    stream_socket_sendto($socket,decbin(rand(0,65536)), 0, $peer);
    usleep(15625);
} while ($pkt !== false);
