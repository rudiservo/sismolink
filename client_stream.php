<?php
$fp = stream_socket_client("udp://127.0.0.1:9999", $errno, $errstr);
if (!$fp) {
    echo "ERROR: $errno - $errstr<br />\n";
} else {
    $t1 = time();
    $i = 0;
    while ((time()-$t1) <= 10) {
        fwrite($fp, "\n");
        echo fread($fp, 26);
        #fclose($fp);
        echo "\n";
        $i++;
    }
    echo "\n\n" . $i . "\n\n";
}
