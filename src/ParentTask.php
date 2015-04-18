<?php

namespace Dwyera;

use Dwyera\Record;

class ParentTask extends Record {

    private $user_task;

    function __construct() {
        parent::__construct();
    }

    public function getUserTask() {
        return $this->user_task;
    }

    public function setUserTask($userTask) {
        $this->user_task = $userTask;
        /* Gets the callbacks from the user task object. 
         * The symfony serializer isn't advanced enough to provide this functionality
         */
        $this->addCallbacks($userTask->getCallbacks());
        return $this;
    }
}
