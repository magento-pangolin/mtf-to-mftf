<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\System\Isolation;

use Magento\Mtf\System\Isolation\Driver;
use Magento\Mtf\Config\DataInterface;

/**
 * Class Listener
 * Listener which provides framework with isolation capability
 *
 * @internal
 */
class Listener implements \PHPUnit\Framework\TestListener
{
    /**
     * Isolation mode
     */
    const MODE_NONE = 'none';
    const MODE_BEFORE = 'before';
    const MODE_AFTER = 'after';
    const MODE_BOTH = 'both';

    /**
     * Isolation scope
     */
    const SCOPE_TEST = 'test';
    const SCOPE_TEST_CASE = 'testCase';
    const SCOPE_TEST_SUITE = 'testSuite';

    /**
     * Is isolation done
     *
     * @var bool
     */
    private $_isolated = false;

    /**
     * Is isolation required before next scope execution
     *
     * @var bool
     */
    private $_isolationRequired = false;

    /**
     * Stack of scopes
     *
     * @var array
     */
    private $_modeStack = [];

    /**
     * Default stack of scopes
     *
     * @var array
     */
    private $_defaultModeStack = [];

    /**
     * Last default modes
     *
     * @var array
     */
    private $_lastDefaultModes = [];

    /**
     * Isolation driver instance
     *
     * @var \Magento\Mtf\System\Isolation\Driver
     */
    private $_driver;

    /**
     * @param \Magento\Mtf\System\Isolation\Driver $driver
     * @param DataInterface $configuration
     */
    public function __construct(Driver $driver, DataInterface $configuration = null)
    {
        if (!isset($configuration)) {
            $configuration = $configuration = \Magento\Mtf\ObjectManagerFactory::getObjectManager()
                ->getInstance()
                ->get('Magento\Mtf\Config\GlobalConfig');
        }
        $this->_driver = $driver;
        $this->_lastDefaultModes = [
            self::SCOPE_TEST_SUITE => $configuration->get('isolation/0/' . self::SCOPE_TEST_SUITE . '/0/value'),
            self::SCOPE_TEST_CASE => $configuration->get('isolation/0/' . self::SCOPE_TEST_CASE . '/0/value'),
            self::SCOPE_TEST => $configuration->get('isolation/0/' . self::SCOPE_TEST . '/0/value'),
        ];
    }

    /**
     * A test suite started
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @return void
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        $scope = $this->_getSuiteScope($suite);
        if (!$scope) {
            return;
        }
        $className = $scope == self::SCOPE_TEST_CASE ? $suite->getName() : get_class($suite);
        $annotations = \PHPUnit\Util\Test::parseTestMethodAnnotations($className);
        $this->_processBeforeScope($annotations['class'], $scope);
    }

    /**
     * A test suite ended
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @return void
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite)
    {
        $this->_isolated = false;
        $scope = $this->_getSuiteScope($suite);
        if (!$scope) {
            return;
        }
        $this->_processAfterScope();
    }

    /**
     * A test started
     *
     * @param \PHPUnit\Framework\Test $test
     * @return void
     */
    public function startTest(\PHPUnit\Framework\Test $test)
    {
        $annotations = \PHPUnit\Util\Test::parseTestMethodAnnotations(get_class($test), $test->getName());
        $this->_processBeforeScope($annotations['method'], self::SCOPE_TEST);
    }

    /**
     * A test ended
     *
     * @param \PHPUnit\Framework\Test $test
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endTest(\PHPUnit\Framework\Test $test, $time)
    {
        $this->_isolated = false;
        $this->_processAfterScope();
    }

    /**
     * Get isolation mode from annotation
     *
     * @param array $annotation
     * @param string $scope
     * @return string
     */
    private function _getMode(array $annotation, $scope)
    {
        if (!isset($annotation['isolation'])) {
            return $this->_lastDefaultModes[$scope];
        }
        $newDefaultModes = $this->_lastDefaultModes;
        $mode = false;
        $available = [self::MODE_BOTH, self::MODE_AFTER, self::MODE_BEFORE, self::MODE_NONE];
        foreach ($annotation['isolation'] as $isolationMode) {
            $isolationMode = trim($isolationMode);
            $scopeDefaultMode = false;
            foreach (array_keys($newDefaultModes) as $defaultModeScope) {
                if (strpos($isolationMode, $defaultModeScope) !== 0) {
                    continue;
                }
                $newDefaultMode = trim(str_replace($defaultModeScope, '', $isolationMode));
                if (in_array($newDefaultMode, $available)) {
                    $newDefaultModes[$defaultModeScope] = $newDefaultMode;
                    $scopeDefaultMode = true;
                }
            }
            if (!$scopeDefaultMode && in_array($isolationMode, $available)) {
                $mode = $isolationMode;
            }
        }
        $mode = $mode ? : $this->_lastDefaultModes[$scope];
        $this->_lastDefaultModes = $newDefaultModes;
        return $mode;
    }

    /**
     * Get suite scope
     *
     * @param \PHPUnit\Framework\TestSuite $suite
     * @return bool|string
     */
    private function _getSuiteScope(\PHPUnit\Framework\TestSuite $suite)
    {
        if (class_exists($suite->getName()) && is_subclass_of($suite->getName(), \PHPUnit\Framework\TestCase::class)) {
            return self::SCOPE_TEST_CASE;
        } elseif (is_subclass_of($suite, \PHPUnit\Framework\TestSuite::class)) {
            return self::SCOPE_TEST_SUITE;
        }
        return false;
    }

    /**
     * Do isolation checks before scope
     *
     * @param array $annotation
     * @param string $scope
     * @return void
     */
    private function _processBeforeScope(array $annotation, $scope)
    {
        $mode = $this->_getMode($annotation, $scope);
        array_push($this->_defaultModeStack, $this->_lastDefaultModes);
        if (!$this->_isolated
            && ($this->_isolationRequired || in_array($mode, [self::MODE_BOTH, self::MODE_BEFORE]))
        ) {
            $this->_driver->isolate();
            $this->_isolated = true;
            $this->_isolationRequired = false;
        }
        array_push($this->_modeStack, $mode);
    }

    /**
     * Do isolation checks after scope
     *
     * @return void
     */
    private function _processAfterScope()
    {
        $mode = array_pop($this->_modeStack);
        $this->_lastDefaultModes = array_pop($this->_defaultModeStack);
        if (in_array($mode, [self::MODE_BOTH, self::MODE_AFTER])) {
            $this->_isolationRequired = true;
        }
    }

    /**
     * An error occurred
     *
     * @param \PHPUnit\Framework\Test $test
     * @param \Exception $e
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addError(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        //
    }

    /**
     * @param \PHPUnit\Framework\Test $test
     * @param \PHPUnit\Framework\Warning $e
     * @param float $time
     */
    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, $time)
    {
        //
    }

    /**
     * A failure occurred
     *
     * @param \PHPUnit\Framework\Test $test
     * @param \PHPUnit\Framework\AssertionFailedError $e
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addFailure(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\AssertionFailedError $e, $time)
    {
        //
    }

    /**
     * Incomplete test
     *
     * @param \PHPUnit\Framework\Test $test
     * @param \Exception $e
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        //
    }

    /**
     * Risky test
     *
     * @param \PHPUnit\Framework\Test $test
     * @param \Exception $e
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        //
    }

    /**
     * Skipped test
     *
     * @param \PHPUnit\Framework\Test $test
     * @param \Exception $e
     * @param float $time
     * @return void
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addSkippedTest(\PHPUnit\Framework\Test $test, \Exception $e, $time)
    {
        //
    }
}
