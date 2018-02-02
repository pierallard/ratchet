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
        $currentClient = $this->getClient($from);
        switch($json->action) {
            case 'authenticate':
                $currentClient->authenticate(intval($json->value));
                break;
            case 'message':
                $message = new Message($currentClient->encrypt($json->value), $currentClient->getAuthentication());
                $message->write($currentClient);
                foreach ($this->getConnectedClients() as $client) {
                    $this->sendMessage($client, $message);
                }
                break;
            case 'delete':
                Message::deleteMessage(intval($json->value));
                foreach ($this->getConnectedClients() as $client) {
                    $client->send(json_encode([
                        'action' => 'delete',
                        'value' => $json->value
                    ]));
                }
                break;
            case 'typing':
                $currentClient->setTyping(intval($json->value) === 1);
                $otherClient = $this->getOtherClient($currentClient);
                $this->sendStatusOfOther($otherClient, $currentClient);
                break;
            case 'password':
                $value = $json->value;
                if (md5($value) === $this->getmd5()) {
                    $from->send(json_encode([
                        'action' => 'password_success'
                    ]));
                    $currentClient->setEncryptor($value);
                    foreach (Message::getMessages() as $message) {
                        $this->sendMessage($this->getClient($from), $message);
                    }
                    echo sprintf("User %s authenticated!\n", $currentClient->getAuthentication());
                    $this->removeOldClients($currentClient->getAuthentication());

                    foreach ($this->getConnectedClients() as $client) {
                        if ($client !== $currentClient) {
                            $client->send(json_encode([
                                'action' => 'otherAuthenticated',
                            ]));
                        }
                    }
                    $otherClient = $this->getOtherClient($currentClient);
                    $this->sendStatusOfOther($currentClient, $otherClient);
                } else {
                    $from->send(json_encode([
                        'action' => 'password_error'
                    ]));
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->getClient($conn)->setConnected(false);
        $currentClient = $this->getClient($conn);
        foreach ($this->getConnectedClients() as $client) {
            $this->sendStatusOfOther($client, $currentClient);
        }
        echo sprintf("User %s disconnect.\n", $currentClient->getAuthentication());
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

    private function sendMessage(Client $client, Message $message) {
        $client->getConn()->send(json_encode([
            'action' => 'message',
            'value' => $message->getValue(),
            'client' => $message->getClient(),
            'time' => $message->getTime()
        ]));
    }

    private function getmd5() {
        return str_replace("\n", '', fgets(fopen('info.log', 'r')));
    }

    private function getConnectedClients(): array {
        return array_filter($this->clients, function (Client $client) {
            return $client->isConnected();
        });
    }

    private function removeOldClients($authentication) {
        for ($i = 0; $i < count(array_keys($this->clients)); $i++) {
            $client = $this->clients[array_keys($this->clients)[$i]];
            if ($client->getAuthentication() === $authentication && !$client->isConnected()) {
                unset($this->clients[array_keys($this->clients)[$i]]);
            }
        }
    }

    private function getOtherClient(Client $currentClient): ?Client {
        foreach ($this->clients as $client) {
            if ($currentClient->getAuthentication() !== $client->getAuthentication()) {
                return $client;
            }
        }

        return null;
    }

    private function sendStatusOfOther(?Client $from, ?Client $otherClient): void {
        if ($otherClient && $from) {
            if ($otherClient->isConnected()) {
                if ($otherClient->isTyping()) {
                    $from->send(json_encode([
                        'action' => 'otherTyping',
                    ]));
                } else {
                    $from->send(json_encode([
                        'action' => 'otherAuthenticated',
                    ]));
                }
            } else {
                $from->send(json_encode([
                    'action' => 'otherUnauthenticated',
                    'time' => $otherClient->getLastActivity()
                ]));
            }
        }
    }
}
