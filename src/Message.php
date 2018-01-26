<?php
namespace MyApp;

class Message {
    const FILE = './error.log';

    /** @var string */
    private $value;

    /** @var int */
    private $client;

    /** @var int */
    private $time;

    public function __construct($value, $client, $time = null) {
        $this->value = $value;
        $this->client = $client;
        $this->time = $time ?? self::milliseconds();
    }

    public static function getMessages(): array {
        $result = [];
        if ($file = fopen(self::FILE, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);
                if (strlen($line) !== 0) {
                    $splitted = preg_split('/ /', $line);
                    $time = intval(array_shift($splitted));
                    $client = intval(array_shift($splitted));
                    $value = str_replace("\n", '', join(' ', $splitted));
                    $result[] = new Message($value, $client, $time);
                }
            }
            fclose($file);
        }

        return $result;
    }

    public static function deleteMessage(int $messageTime) {
        $result = '';
        if ($file = fopen(self::FILE, 'r')) {
            while (!feof($file)) {
                $line = fgets($file);
                if (strlen($line) !== 0) {
                    $splitted = preg_split('/ /', $line);
                    $time = intval(array_shift($splitted));
                    if ($time !== $messageTime) {
                        $result .= $line;
                    }
                }
            }
            fclose($file);
        }

        file_put_contents(self::FILE, $result);
    }

    public function write(Client $client) {
        $line = sprintf("%s %s %s\n", self::milliseconds(), $this->client, $this->value);
        file_put_contents(self::FILE, $line, FILE_APPEND);
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getClient(): int {
        return $this->client;
    }

    private static function milliseconds() {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }

    public function getTime() {
        return $this->time;
    }
}
