<?php

require_once __DIR__ . '/ExceptionExpection.php';

class SimpleMock_Recorder {

    public function __construct($testCase, $class) {
        $this->testCase = $testCase;
        $this->class = $class;
        $this->methods = array();
        $this->explicitInvocationCounts = array();
        $this->args = array();
        $this->returns = array();
        $this->invocationNumber = 0;
    }

    public function expects($method) {
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
        $simpleMock = $this->testCase->getMock($this->class, $this->methods);
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
}
