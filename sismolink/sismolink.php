<?php

namespace sismolink;

class sismolink
{

    private $db;
    private $devices = null;
    private $event_log = false;
    private $heartbeat_log = false;
    private $stream;
    private $stream_ip;
    private $stream_port;
    private $webservice_port;
    private $push_port;
    private $previous = 0;
    private $event_stm;
    private $heartbeat_stm;

    public $verbose = false;


    public function __construct(array $config)
    {
        if (array_key_exists('db', $config)) {
            $this->db = new \PDO($config['db']['driver'] . ':dbname=' . $config['db']['schema'] . ';host=' . $config['db']['host'], $config['db']['user'], $config['db']['pass']);
            $this->generateDevices();
        }
        if (array_key_exists('server', $config)) {
            $this->ip = $config['server']['ip'];
            $this->event_port = $config['server']['event_port'];
            $this->heartbeat_port = $config['server']['heartbeat_port'];
        }
        if (array_key_exists('event_log', $config) && $config['event_log']) {
            $this->event_log = true;
        }
        if (array_key_exists('heartbeat_log', $config) && $config['heartbeat_log']) {
            $this->heartbeat_log = true;
        }
        if (array_key_exists('stream', $config) && $config['push_stream']) {
            $this->stream = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            $this->stream_ip = $config['stream']['ip'];
            $this->stream_port = $config['stream']['port'];
        }
        if (array_key_exists('push_port', $config)) {
            $this->push_port = $config['push_port'];
        }
        if (array_key_exists('webservice_port', $config)) {
            $this->webservice_port = $config['webservice_port'];
        }
    }

    public function generateDevices()
    {
        if (!$this->devices) {
            $devices = $this->db->query('select * from devices')->fetchAll();
            if ($devices) {
                $this->devices = [];
            }
            foreach ($devices as $key => $row) {
                $this->devices[$row['ip']] = $row['id'];
            }
        }
    }

    public function getDeviceId($ip)
    {
        if (array_key_exists($ip, $this->devices)) {
            return $this->devices[$ip];
        }
        return null;
    }

    public function createEventServer()
    {
        $this->event_stm = $this->db->prepare('insert into devices_events (`id_device`, `value`) values (:i, :v)');
        $previous = 0;
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);
        $factory->createServer($this->ip . ':' . $this->event_port)->then(function (\React\Datagram\Socket $server) {
            $server->on('message', function($message, $address, $server) {
                $id = $this->getDeviceId(explode(":", $address)[0]);
                if ($id) {
                    $value = (int) $message;
                    if (!$this->previous && $value) {
                        $this->event_stm->execute(['i' => $id, 'v' => 0]);
                        if ($this->verbose) {
                            echo 'Event ' . microtime() . " | " . $id . ' : ' . 0 . PHP_EOL;
                        }
                    }
                    if ($value || (!$this->previous && $value) ||  ($this->previous && !$value)) {
                        $this->previous = $value;
                        if (!$this->event_stm->execute(['i' => $id, 'v' => $value]) && $this->verbose) {
                            echo "\nPDO::errorInfo():\n";
                            print_r($this->db->errorInfo());
                        }
                        if ($this->verbose) {
                            echo 'Event ' . microtime() . " | " . $id . ' : ' . $value . PHP_EOL;
                        }
                    }
                    #echo 'Event '. " | " . $id . ' : ' . $value . PHP_EOL;
                    $this->streamOut($id,$message);
                }
            });
        });
        $loop->run();
    }

    public function createHeartBeatServer()
    {
        $this->heartbeat_stm = $this->db->prepare('insert into devices_heartbeat (id_device) values (:id)');
        $loop = \React\EventLoop\Factory::create();
        $factory = new \React\Datagram\Factory($loop);
        $factory->createServer($this->ip . ':' . $this->heartbeat_port)->then(function (\React\Datagram\Socket $server) {
            $server->on('message', function($message, $address, $server) {
                $id = $this->getDeviceId($address);
                if ($id) {
                    $this->heartbeat_stm->execute(['id' -> $id]);
                    if ($this->verbose) {
                        echo 'HeartBeat ' . $address . PHP_EOL;
                    }
                    
                }
            });
        });
        $loop->run();
    }

    public function addDevice($ip, $name)
    {
        $stm = $this->db->prepare('insert into devices (ip, name) values (:ip, :n)');
        $stm->execute(['ip' => $ip, 'n' => $name]);
    }

    public function updateDevice($id, $ip, $name)
    {
        $stm = $this->db->prepare('update devices set (ip, name) values (:i, :n) where id=:id');
        $stm->execute(['ip' => $ip, 'n' => $name, 'id' => $id]);
    }

    public function removeDevice($id)
    {
        $stm = $this->db->prepare('delete from devices where id=:id');
        $stm->execute(['id' => $id]);
    }

    public function run()
    {
        if ($this->event_log) {
            $this->createEventServer();
        }
        if ($this->heartbeat_log) {
            $this->createHeartBeatServer();
        }
    }

    public function streamOut($id, $value)
    {
        if ($this->stream) {
            $msg = $id . ';' . $value;
            $len = strlen($msg);
            socket_sendto($this->stream, $msg, $len, 0, $this->stream_ip, $this->stream_port);
        }
    }
    
    public function startPusher()
    {
        $loop   = \React\EventLoop\Factory::create();
        $pusher = new Pusher($loop, $this->stream_ip, $this->stream_port);
        $webSock = new \React\Socket\Server($loop);
        $webSock->listen($this->push_port, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
        $webServer = new \Ratchet\Server\IoServer(
            new \Ratchet\Http\HttpServer(
                new \Ratchet\WebSocket\WsServer(
                    $pusher
                )
            ),
            $webSock
        );
        $loop->run();
    }
    
    public function startWebService()
    {
        
        $app = function ($request, $response) {
    $response->writeHead(200, array('Content-Type' => 'text/plain'));
    $response->end("Hello World\n");
};

$loop = \React\EventLoop\Factory::create();
$socket = new \React\Socket\Server($loop);
$http = new \React\Http\Server($socket, $loop);

$http->on('request', $app);
echo "Server running at http://127.0.0.1:1337\n";

$socket->listen(1337);
$loop->run();
        /*
        $loop = \React\EventLoop\Factory::create();
        $socket = new \React\Socket\Server($this->webservice_port, $loop);

        $http = new \React\Http\Server($socket);
        $http->on('request', function (Request $request, Response $response) {
            $response->writeHead(200, array('Content-Type' => 'text/plain'));
            $response->end("Hello World!\n");
        });
        $loop->run();
        */
    }
}
