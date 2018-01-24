<?php
namespace MyApp;
use Ratchet\ConnectionInterface;

class Client {
    /** @var ConnectionInterface */
    private $conn;

    /** @var int */
    private $authentication;

    public function __construct(ConnectionInterface $conn) {
        $this->conn = $conn;
    }

    public function send($msg) {
        $this->conn->send($msg);
    }

    public function authenticate(int $value) {
        $this->authentication = $value;
    }

    public function getAuthentication(): int {
        return $this->authentication;
    }

    public function getConn(): ConnectionInterface {
        return $this->conn;
    }
}
