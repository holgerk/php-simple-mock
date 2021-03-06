<?php

require_once __DIR__ . '/ExceptionExpection.php';

class SimpleMock_Recorder {

    public function __construct($testCase, $arguments) {
        $this->testCase = $testCase;
        $this->phpunitGetMockArguments = $arguments;
        $this->methods = array();
        $this->explicitInvocationCounts = array();
        $this->args = array();
        $this->returns = array();
        $this->invocationNumber = 0;

        $this->strict = false; // strict mode - one can only mock what allready exists
        $this->complete = false; // complete mode - one can only call what is mocked

        // TODO document this behaviour
        if (count($this->phpunitGetMockArguments) == 1) {
            $this->phpunitGetMockArguments[] = array(); // constructor args
            $this->phpunitGetMockArguments[] = '';      // mock class name
            $this->phpunitGetMockArguments[] = false;   // call constructor
        }
    }

    public function strict() {
        $this->strict = true;
        return $this;
    }

    public function complete() {
        $this->complete = true;
        return $this;
    }

    public function expects($method) {
        $this->checkStrict($method);
        $this->methods[] = $method;
        return $this;
    }

    public function never() {
        $this->explicitInvocationCounts[$this->currentMethod()] = 0;
        return $this;
    }

    public function with() {
        $this->args[$this->currentMethod()][] = func_get_args();
        return $this;
    }

    public function returns() {
        foreach (func_get_args() as $returnValue) {
            $this->returns[$this->currentMethod()][] = $returnValue;
        }
        return $this;
    }

    public function raises($exception) {
        $this->returns(new SimpleMock_ExceptionExpection($exception));
        return $this;
    }

    public function create() {
        $this->handleCompleteMode();

        $simpleMock = $this->instantiateMock();
        foreach ($this->methods as $method) {
            $expectedInvocationCount = $this->expectedInvocationCount($method);
            if ($expectedInvocationCount == 0) {
                $phpunitRecorder = $simpleMock->expects(PHPUnit_Framework_TestCase::never());
                $phpunitRecorder->method($method);
            } else if ($expectedInvocationCount == 1) {
                $phpunitRecorder = $simpleMock->expects(PHPUnit_Framework_TestCase::once());
                $phpunitRecorder->method($method);
                $this->setExpectedArguments($phpunitRecorder, $method);
                $this->setExpectedResponse($phpunitRecorder, $method);
                $this->invocationNumber++;
            } else {
                for ($invocationNumber = 0; $invocationNumber < $expectedInvocationCount; $invocationNumber++) {
                    $phpunitRecorder = $simpleMock->expects(PHPUnit_Framework_TestCase::at($this->invocationNumber));
                    $phpunitRecorder->method($method);
                    $this->setExpectedArguments($phpunitRecorder, $method, $invocationNumber);
                    $this->setExpectedResponse($phpunitRecorder, $method, $invocationNumber);
                    $this->invocationNumber++;
                }
            }
        }

        return $simpleMock;
    }

    protected function instantiateMock() {
        array_splice($this->phpunitGetMockArguments, 1, 0, array($this->methods));
        return call_user_func_array(
            array($this->testCase, 'getMock'),
            $this->phpunitGetMockArguments);
    }

    protected function currentMethod() {
        return $this->methods[count($this->methods)-1];
    }

    protected function expectedInvocationCount($method) {
        if (isset($this->explicitInvocationCounts[$method])) {
            return $this->explicitInvocationCounts[$method];
        }

        // implicit invocation counts
        $returnCount = 0;
        $argCount = 0;
        if (isset($this->returns[$method])) {
            $returnCount = count($this->returns[$method]);
        }
        if (isset($this->args[$method])) {
            $argCount = count($this->args[$method]);
        }
        return max($argCount, $returnCount, 1);
    }

    protected function setExpectedArguments($phpunitRecorder, $method, $invocationNumber = 0) {
        if (isset($this->args[$method])) {
            $args = $this->args[$method][count($this->args[$method]) - 1];
            if (isset($this->args[$method][$invocationNumber])) {
                $args = $this->args[$method][$invocationNumber];
            }
            foreach ($args as &$arg) {
                if ($arg instanceof PHPUnit_Framework_Constraint) {
                    // no processing required
                } else {
                    $arg = PHPUnit_Framework_Assert::equalTo($arg);
                }
            }
            call_user_func_array(array($phpunitRecorder, 'with'), $args);
        }
    }

    protected function setExpectedResponse($phpunitRecorder, $method, $invocationNumber = 0) {
        if (isset($this->returns[$method])) {
            $returns = $this->returns[$method];
            $returnValue = $returns[count($returns) - 1];
            if (isset($returns[$invocationNumber])) {
                $returnValue = $returns[$invocationNumber];
            }
            if ($returnValue instanceof SimpleMock_ExceptionExpection) {
                $phpunitRecorder->will(PHPUnit_Framework_TestCase::throwException($returnValue->getException()));
            } else {
                $phpunitRecorder->will(PHPUnit_Framework_TestCase::returnValue($returnValue));
            }
        }
    }

    private function checkStrict($methodName) {
        if (!$this->strict) {
            return;
        }
        $class = $this->phpunitGetMockArguments[0];
        if (!class_exists($class)) {
            throw new Exception("Strict-Mode-Error: $class does not exist!");
        }
        if (!in_array($methodName, get_class_methods($class))) {
            throw new Exception("Strict-Mode-Error: $class has no $methodName method!");
        }
    }

    private function handleCompleteMode() {
        if (!$this->complete) {
            return;
        }
        $class = $this->phpunitGetMockArguments[0];
        if (!class_exists($class)) {
            throw new Exception("Complete-Mode-Error: $class does not exist!");
        }
        $notMockedMethods = array_diff(get_class_methods($class), $this->methods);
        foreach ($notMockedMethods as $method) {
            $this->expects($method)->never();
        }
    }
}

