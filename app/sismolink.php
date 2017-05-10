<?php

namespace sismolink;

class App
{

    private $config_path
    private $connectors;
    private $sockets;
    private $web_server;
    private $db;

    public function __construct()
    {
        $this->connectors = [];
        
    }

    public function run($config)
    {
        if (array_key_exists('db', $config)) {
            #$this->db = new PDO($db['dsn'], $db['user'], $db['pass']);
            $this->db = $config['db'];
        }
        if (array_key_exists('devices', $config)) {
            foreach ($config['devices'] as $dev) {
                $this->connectors[] = exec('php connector\connector.php ' . $dev['id'] . $this->config_path . '> /dev/null & echo $!'),
            }
        }
    }
}
