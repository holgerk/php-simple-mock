<?php

require_once __DIR__ . '/../lib/SimpleMock/TestCase.php';

class SimpleMockParameterTest extends SimpleMock_TestCase {

    protected $receivedParams = array();

    // overwrite/disable phpunit's default, so we can verify that all params forwarded
    public function getMock() {
        $this->receivedParams = func_get_args();
        return call_user_func_array('parent::getMock', $this->receivedParams);
    }

    // overwrite/disable phpunit's default verification, because we do not need it in this context
    protected function verifyMockObjects() {
    }

    public function testParamForwarding() {
        $this->simpleMock('SomeClass')->create();
        $this->assertEquals(array('SomeClass', array(), array(), '', false), $this->receivedParams);
    }

    public function testParamForwardingWithMethods() {
        $this->simpleMock('SomeClass')->expects('m1')->expects('m2')->create();
        $this->assertEquals(array('SomeClass', array('m1', 'm2'), array(), '', false), $this->receivedParams);
    }

    public function testParamForwardingWithAdditionalParams() {
        $this->simpleMock('SomeClass')->expects('m1')->expects('m2')->create();
        $this->assertEquals(array('SomeClass', array('m1', 'm2'), array(), '', false), $this->receivedParams);
    }

    public function testParamForwardingWithArbitaryAdditionalParams() {
        $this->simpleMock('SomeClass', array(), 'bla', false, true, false)->create();
        $this->assertEquals(
            array('SomeClass', array(), array(), 'bla', false, true, false),
            $this->receivedParams);
    }
}
