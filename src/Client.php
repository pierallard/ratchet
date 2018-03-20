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

    /** @var bool */
    private $isConnected;

    /** @var int */
    private $lastActivity;

    /** @var bool */
    private $isTyping;

    public function __construct(ConnectionInterface $conn) {
        $this->conn = $conn;
    }

    public function send($msg) {
        $this->conn->send($msg);
    }

    public function authenticate(int $value) {
        $this->authentication = $value;
        $this->isConnected = true;
        $this->lastActivity = $this->milliseconds();
    }

    public function getAuthentication():? int {
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

    public function setConnected($value) {
        $this->isConnected = $value;
        $this->lastActivity = $this->milliseconds();
    }

    public function isConnected(): bool {
        return !!$this->isConnected;
    }

    public function getLastActivity(): int {
        return $this->lastActivity;
    }

    private static function milliseconds() {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }

    public function setTyping($value) {
        $this->isTyping = $value;
        $this->lastActivity = $this->milliseconds();
    }

    public function isTyping() {
        return !!$this->isTyping;
    }
}
