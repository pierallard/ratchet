<?php
namespace MyApp;
use Ratchet\ConnectionInterface;

class Client {
    /** @var ConnectionInterface */
    private $conn;

    /** @var int */
    private $authentication;

    /** @var string */
    private $encryptor;

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

    public function setEncryptor($value) {
        $this->encryptor = $value;
    }

    public function encrypt($value): string {
        $chars = $this->mbStringToArray($value);
        $encrypted = '';
        for ($i = 0; $i < count($chars); $i++) {
            $letter = $this->encryptor[$i % strlen($this->encryptor)];
            $encrypted .= mb_chr(mb_ord($chars[$i]) + mb_ord($letter));
        }

        return $encrypted;
    }

    private function mbStringToArray($string) {
        $array = [];
        $strlen = mb_strlen($string);

        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "UTF-8");
            $string = mb_substr($string, 1, $strlen, "UTF-8");
            $strlen = mb_strlen($string);
        }

        return $array;
    }
}
