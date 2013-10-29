<?php

require_once __DIR__ . '/../src/SimpleMock/TestCase.php';

class DemoClass {
    public function method1($param1) {}
    public function method2($param1) {}
}

class SimpleMockTest extends SimpleMock_TestCase {

    /**
     * @expectedException PHPUnit_Framework_ExpectationFailedException
     */
    public function testMissingCallException() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->create();
        try {
            $this->verify($simpleMock);
        } catch (Exception $e) {
            $this->assertEquals(
                'Expectation failed for method name is equal to <string:someMethod> when ' .
                "invoked 1 time(s).\nMethod was expected to be called 1 times, actually called 0 times.",
                $e->getMessage());
            throw $e;
        }
    }

    public function testWrongParameterException() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with(23, 42)
            ->create();
        try {
            $simpleMock->someMethod(23, 'args');
        } catch (Exception $e) {
            $this->assertEquals(42, $e->getComparisonFailure()->getExpected());
        }
    }

    public function testReturnValue() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->returns('return value')
            ->create();
        $this->assertEquals('return value', $simpleMock->someMethod());
    }

    public function testDifferentReturnValues() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->returns('return value', 'second return value')
            ->create();
        $this->assertEquals('return value', $simpleMock->someMethod());
        $this->assertEquals('second return value', $simpleMock->someMethod());
    }

    public function testDifferentReturnValues2() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->returns('return value')
            ->returns('second return value')
            ->create();
        $this->assertEquals('return value', $simpleMock->someMethod());
        $this->assertEquals('second return value', $simpleMock->someMethod());
    }

    public function testDifferentParameterExpectations() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with(42)
            ->with('right arg')
            ->create();
        $simpleMock->someMethod(42);
        try {
            $simpleMock->someMethod('wrong arg');
        } catch (Exception $e) {
            $this->assertEquals('right arg', $e->getComparisonFailure()->getExpected());
            $this->assertEquals('wrong arg', $e->getComparisonFailure()->getActual());
        }
    }

    public function testThatPhpunitMatchersStillWork() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with($this->stringContains('seed'))
            ->create();
        $simpleMock->someMethod('string containing seed');
    }

    public function testMethodShouldNeverCalled() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->never()
            ->create();
        try {
            $simpleMock->someMethod();
            $this->fail('has not raised');
        } catch (Exception $e) {
            $this->assertEquals(
                'SomeClass::someMethod() was not expected to be called.',
                $e->getMessage()
            );
        }
    }

    public function testRaises() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->raises(new Exception('exception message'))
            ->create();
        try {
            $simpleMock->someMethod();
            $this->fail('has not raised');
        } catch (Exception $e) {
            $this->assertEquals(
                'exception message',
                $e->getMessage()
            );
        }
    }

    public function testMultipleMethods() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')->with(1)->returns('first')
            ->expects('someOtherMethod')->with(2)->returns('second')
            ->create();
        $this->assertEquals('first', $simpleMock->someMethod(1));
        $this->assertEquals('second', $simpleMock->someOtherMethod(2));
        $this->verify($simpleMock);
    }

    public function testMultipleMethodsReversedCallOrder() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')->with(1)->returns('first')
            ->expects('someOtherMethod')->with(2)->returns('second')
            ->create();
        $this->assertEquals('second', $simpleMock->someOtherMethod(2));
        $this->assertEquals('first', $simpleMock->someMethod(1));
        $this->verify($simpleMock);
    }

    public function testUnorderedWithOrderedInvocation() {
        $simpleMock = $this->simpleMock('SomeClass')
            ->expects('someMethod')->with(1)->returns('first')       // <-- unordered
            ->expects('someOtherMethod')->with(2)->returns('second') // <-- unordered
            ->expects('thirdMethod')
                ->with(3)->returns('third') // <-- ordered call
                ->with(4)->returns('fourth') // <-- ordered call
            ->create();
        $this->assertEquals('second', $simpleMock->someOtherMethod(2));
        $this->assertEquals('first', $simpleMock->someMethod(1));
        $this->assertEquals('third', $simpleMock->thirdMethod(3));
        $this->assertEquals('fourth', $simpleMock->thirdMethod(4));
        $this->verify($simpleMock);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Strict-Mode-Error: DemoClass has no method3 method!
     */
    public function testCanOnlyMockExistingsMethodsInStrictMode() {
        $simpleMock = $this->simpleMock('DemoClass')
            ->strict()
            ->expects('method2')
            ->expects('method3') // <- should throw because method3 does not exist
            ->create();
    }

    // overwrite/disable phpunit's default verification, so we can test against it
    protected function verifyMockObjects() {
    }

    protected function verify($simpleMock) {
        $simpleMock->__phpunit_verify();
        $simpleMock->__phpunit_cleanup();
    }
}


