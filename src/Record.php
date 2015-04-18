<?php

namespace Dwyera;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class Record {

    protected $encoder;
    protected $normalizer;
    protected $callbacks = array();

    function __construct() {
        $this->encoder = new JsonEncoder();
        $this->normalizer = new PropertyNormalizer();
    }

    protected function setCallbacks($callbacks) {
        $this->normalizer->setCallbacks($callbacks);
    }

    public function addCallbacks(array $callbacks) {
        $this->callbacks = array_merge($this->callbacks, $callbacks);
    }

    public function serialize() {

        $this->normalizer->setIgnoredAttributes(array('encoder', 'normalizer', 'callbacks'));
        $this->getNormalizer()->setCallbacks($this->callbacks);
        $serializer = new Serializer(array($this->normalizer), array($this->encoder));
        $jsonContent = $serializer->serialize($this, 'json');
        return $jsonContent;
    }

    public function convertDate($dateTime) {
        return $dateTime instanceof \DateTime ? $dateTime->format(\DateTime::ISO8601) : '';
    }

    protected function getNormalizer() {
        return $this->normalizer;
    }

}
