<?php

namespace Dwyera;

use Dwyera\Record;

class UserTask extends Record {

    private $space_id;
    private $ticket_id;
    private $description;
    private $hours;
    private $begin_at;
    private $end_at;

    public function getSpaceId() {
        return $this->space_id;
    }

    public function setSpaceId($space_id) {
        $this->space_id = $space_id;
    }

    public function getTicketId() {
        return $this->ticket_id;
    }

    public function setTicketId($ticket_id) {
        $this->ticket_id = $ticket_id;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getHours() {
        return $this->hours;
    }

    public function setHours($hours) {
        $this->hours = $hours;
    }

    public function getBeginAt() {
        return $this->begin_at;
    }

    public function setBeginAt($begin_at) {
        $this->begin_at = $begin_at;
    }

    public function getEndAt() {
        return $this->end_at;
    }

    public function setEndAt($end_at) {
        $this->end_at = $end_at;
    }

    public function getCallbacks() {
        return array('begin_at' => array($this, 'convertDate'), 'end_at' => array($this, 'convertDate'));
    }

}
