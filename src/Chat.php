<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    /** @var Client[] */
    protected $clients;

    public function __construct() {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[] = new Client($conn);

        echo "New connection!\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $json = json_decode($msg);
        switch($json->action) {
            case 'authenticate':
                $this->getClient($from)->authenticate(intval($json->value));
                foreach ($this->clients as $client) {
                    $client->send(json_encode([
                        'action' => 'authenticate',
                        'client' => $this->getClient($from)->getAuthentication()
                    ]));
                }
                break;
            case 'message':
                foreach ($this->clients as $client) {
                    $client->send(json_encode([
                        'action' => 'message',
                        'value' => $json->value,
                        'client' => $this->getClient($from)->getAuthentication()
                    ]));
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        for ($i = 0; $i < count(array_keys($this->clients)); $i++) {
            if ($this->clients[array_keys($this->clients)[$i]]->getConn() === $conn) {
                echo "Connection {$this->clients[array_keys($this->clients)[$i]]->getAuthentication()} has disconnected\n";
                unset($this->clients[array_keys($this->clients)[$i]]);

                return;
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function getClient($from): Client {
        foreach ($this->clients as $client) {
            if ($client->getConn() == $from) {
                return $client;
            }
        }

        throw new \Exception('No client found');
    }
}
