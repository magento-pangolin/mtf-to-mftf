<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\TestSuite;

/**
 * Class Callback
 * Simple wrapper over regular Test Suite to provide ability for callbacks prior Test Suite run
 *
 * @api
 */
class Callback extends \PHPUnit\Framework\TestSuite
{
    /**
     * @var Callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var \Magento\Mtf\TestSuite\TestSuiteFactory
     */
    protected $factory;

    /**
     * @param TestSuiteFactory $factory
     * @param array $arguments
     * @param mixed $theClass
     * @param string $name
     */
    public function __construct(
        \Magento\Mtf\TestSuite\TestSuiteFactory $factory,
        array $arguments = [],
        $theClass = '',
        $name = ''
    ) {
        $this->factory = $factory;
        $this->arguments = $arguments;
        parent::__construct($theClass, $name);
    }

    /**
     * Run callback
     *
     * @param \PHPUnit\Framework\TestResult $result
     * @return \PHPUnit\Framework\TestResult | void
     */
    public function run(\PHPUnit\Framework\TestResult $result = null)
    {
        $testClass = $this->factory->create($this->getName(), $this->arguments);
        return $testClass->run($result);
    }

    /**
     * Avoid attempt to serialize callback
     *
     * @return array
     */
    public function __sleep()
    {
        return ['arguments'];
    }
}
