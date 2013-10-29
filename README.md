php-simple-mock
===============

Create PHPUnit Mocks without pain.

Tested with: PHP 5.3.6 and PHPUnit 3.5.5

<!-- INSERT: "test/ExampleTest.php" -->
test/ExampleTest.php
---
```php
<?php

require_once __DIR__ . '/../lib/SimpleMock/TestCase.php';

class ExampleTest extends SimpleMock_TestCase {
    // SimpleMock_TestCase extends PHPUnit_Framework_TestCase and adds the method: "simpleMock".
    // If you don't want to extend SimpleMock_TestCase then you can copy the: "simpleMock"
    // method to your own BaseClass or whatever.

    public function testBasicMockUsage() {
        // create a mock which expects that someMethod is called once
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->create();
        // The param: "SomeClass" corresponds to phpunit's first param on the getMock method.
        // The +create+ call returns the mock. This is should allways be the last call.

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod'));
        //   $mock->expects($this->once())
        //        ->method('someMethod');

        // satisfy the expectations
        $mock->someMethod();
    }

    public function testSetupParameterExpectations() {
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with(1, 2, 3)
            ->create();

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod'));
        //   $mock->expects($this->once())
        //        ->method('someMethod')
        //        ->with($this->equalTo(1), $this->equalTo(2), $this->equalTo(3));

        // satisfy the expectations
        $mock->someMethod(1, 2, 3);
    }

    public function testSetupReturnValue() {
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with(1, 2, 3)
            ->returns('one two three')
            ->create();

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod'));
        //   $mock->expects($this->once())
        //        ->method('someMethod')
        //        ->with($this->equalTo(1), $this->equalTo(2), $this->equalTo(3))
        //        ->will($this->returnValue('one two three'));

        // satisfy the expectations
        $this->assertEquals('one two three', $mock->someMethod(1, 2, 3));
    }

    public function testConsecutiveCalls() {
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
                ->with(1)->returns('one') // first call
                ->with(2)->returns('two') // second call
            ->expects('someOtherMethod')
                ->returns('three')        // third call
            ->create();

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod', 'someOtherMethod'));
        //   $mock->expects($this->at(0))
        //        ->method('someMethod')
        //        ->with($this->equalTo(1))
        //        ->will($this->returnValue('one'));
        //   $mock->expects($this->at(1))
        //        ->method('someMethod')
        //        ->with($this->equalTo(2))
        //        ->will($this->returnValue('two'));
        //   $mock->expects($this->at(2))
        //        ->method('someOtherMethod')
        //        ->with($this->equalTo(3))
        //        ->will($this->returnValue('three'));

        // satisfy the expectations
        $this->assertEquals('one', $mock->someMethod(1));
        $this->assertEquals('two', $mock->someMethod(2));
        $this->assertEquals('three', $mock->someOtherMethod(3));
    }

    public function testExceptionExpectation() {
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->raises(new Exception('damned'))
            ->create();

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod'));
        //   $mock->expects($this->once())
        //        ->method('someMethod')
        //        ->will($this->throwException(new Exception('damned')));

        // satisfy the expectations
        try {
            $mock->someMethod();
            $this->fail('has not thrown');
        } catch (Exception $e) {
            $this->assertEquals('damned', $e->getMessage());
        }
    }

    public function testUsingPHPUnitMatchers() {
        $mock = $this->simpleMock('SomeClass')
            ->expects('someMethod')
            ->with($this->stringContains('hello'))
            ->create();

        // Same with pure PHPUnit:
        //   $mock = $this->getMock('SomeClass', array('someMethod'));
        //   $mock->expects($this->once())
        //        ->method('someMethod')
        //        ->with($this->stringContains('hello'));

        // satisfy the expectations
        $mock->someMethod('foo hello bar');
    }
}
```
<!-- /INSERT -->

Credits
=======

Thanks phpunit for beeing an awesome unit testing framework.
Thanks ruby mocha for the inspiring mock-api.

* http://www.phpunit.de/
* http://gofreerange.com/mocha/


License
=======

php-simple-mock is released under the MIT license:

* http://www.opensource.org/licenses/MIT
