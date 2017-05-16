<?php
require 'vendor/autoload.php';
include 'sismolink/sismolink.php';
include 'sismolink/pusher.php';

$sismo =  new sismolink\SismoLink(include 'config/config.php');

$short_options ='';

$long_options = [
    'ip:',
    'id:',
    'name:',
    'add-device',
    'update-device',
    'delete-device',
    'start',
    'verbose',
    'websocket',
    'webservice'
];


$options = getopt($short_options, $long_options);

if ($options) {
    if (array_key_exists('verbose', $options)) {
        $sismo->verbose = true;
    }
    if (array_key_exists('add-device', $options) && array_key_exists('ip', $options) && array_key_exists('name', $options))  {
        $sismo->addDevice($options['ip'], $options['name']);
    } else if (array_key_exists('update-device', $options) && array_key_exists('id', $options) && array_key_exists('ip', $options) && array_key_exists('name', $options))  {
        $sismo->updateDevice($options['id'], $options['ip'], $options['name']);
    } else if (array_key_exists('delete-device', $options) && array_key_exists('id', $options))  {
        $sismo->deleteDevice($options['id'], $options['ip'], $options['name']);
    } else if (array_key_exists('start', $options)) {
        $sismo->run();
    } else if (array_key_exists('websocket', $options)) {
        $sismo->startPusher();
    }
    else if (array_key_exists('webservice', $options)) {
        $sismo->startWebService();
    }
}
