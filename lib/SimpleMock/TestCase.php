<?php

require_once __DIR__ . '/Recorder.php';

class SimpleMock_TestCase extends PHPUnit_Framework_TestCase {

    protected function simpleMock($class) {
        return new SimpleMock_Recorder($this, $class);
    }
}
