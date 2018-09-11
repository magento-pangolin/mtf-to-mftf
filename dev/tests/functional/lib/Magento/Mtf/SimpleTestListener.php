<?php

namespace Magento\Mtf;

use PHPUnit\Framework\TestListener;

class SimpleTestListener implements TestListener
{
    public function addError(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        // TODO: Implement addError() method.
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, $time)
    {
        // TODO: Implement addWarning() method.
    }

    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, $time)
    {
        // TODO: Implement addFailure() method.
    }

    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        // TODO: Implement addIncompleteTest() method.
    }

    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        // TODO: Implement addRiskyTest() method.
    }

    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        // TODO: Implement addSkippedTest() method.
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        // TODO: Implement startTestSuite() method.
    }

    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        // TODO: Implement endTestSuite() method.
    }

    public function startTest(\PHPUnit\Framework\Test $test)
    {
        printf("startTest()");
    }

    public function endTest(\PHPUnit\Framework\Test $test, $time)
    {
        printf("endTest()");
    }
}
?>