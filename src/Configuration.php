<?php

namespace Dwyera;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

class Configuration extends Record {

    protected $key;
    protected $secret;

    const CONFIG = 'app/config/app.json';

    public function __construct() {
        parent::__construct();
    }

    public function getKey() {
        return $this->key;
    }

    public function getSecret() {
        return $this->secret;
    }

    public function setKey($key) {
        $this->key = $key;
        return $this;
    }

    public function setSecret($secret) {
        $this->secret = $secret;
        return $this;
    }

    public function saveConfig() {
        $this->write(self::CONFIG, $this->serialize());
    }

    public static function readConfig() {
        $configStr = self::read(getcwd() . DIRECTORY_SEPARATOR . self::CONFIG);
        $encoder = new JsonEncoder();
        $normalizer = new PropertyNormalizer();
        $serializer = new Serializer(array($normalizer), array($encoder));
        return $serializer->deserialize($configStr, 'Dwyera\Configuration', 'json');
    }

    public function write($filename) {
        $fs = new \Symfony\Component\Filesystem\Filesystem;
        $fs->dumpFile($filename, $this->serialize());
    }

    public static function read($filename) {
        return file_get_contents($filename);
    }

}
