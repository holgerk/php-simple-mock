<?php

class SimpleMock_ExceptionExpection {
    function __construct($exception) {
        $this->exception = $exception;
    }

    function getException() {
        return $this->exception;
    }
}
