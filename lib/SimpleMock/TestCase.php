<?php

require_once __DIR__ . '/Recorder.php';

class SimpleMock_TestCase extends PHPUnit_Framework_TestCase {

    // use same params as with phpunits getMock method, but omit the second param
    protected function simpleMock() {
        return new SimpleMock_Recorder($this, func_get_args());
    }
}
