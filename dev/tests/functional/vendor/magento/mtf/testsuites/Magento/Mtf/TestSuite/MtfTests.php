<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\TestSuite;

use Magento\Mtf\ObjectManager;
use Magento\Mtf\ObjectManagerFactory;

/**
 * Class runner test suite.
 */
class MtfTests extends \PHPUnit_Framework_TestSuite
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_TestSuite
     */
    protected $suite;

    /**
     * @var \PHPUnit_Framework_TestResult
     */
    protected $result;

    /**
     * Run collected tests.
     *
     * @param \PHPUnit_Framework_TestResult $result
     * @param bool $filter
     * @param array $groups
     * @param array $excludeGroups
     * @param bool $processIsolation
     *
     * @return \PHPUnit_Framework_TestResult|void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function run(
        \PHPUnit_Framework_TestResult $result = null,
        $filter = false,
        array $groups = [],
        array $excludeGroups = [],
        $processIsolation = false
    ) {
        if ($result === null) {
            $this->result = $this->createResult();
        }
    }

    /**
     * Prepare test suite.
     *
     * @return mixed
     */
    public static function suite()
    {
        $suite = new self();
        return $suite->prepareSuite();
    }

    /**
     * Prepare test suite and apply application state.
     *
     * @return \Magento\Mtf\TestSuite\AppState
     */
    public function prepareSuite()
    {
        $this->init();
        return $this->objectManager->create('Magento\Mtf\TestSuite\AppState');
    }

    /**
     * Call the initialization of ObjectManager.
     */
    public function init()
    {
        $this->initObjectManager();
    }

    /**
     * Initialize ObjectManager.
     *
     * @return void
     */
    private function initObjectManager()
    {
        if (!isset($this->objectManager)) {
            $objectManagerFactory = new ObjectManagerFactory();

            $configFileName = isset($_ENV['configuration:Magento/Mtf/TestSuite/MtfTests'])
                ? $_ENV['configuration:Magento/Mtf/TestSuite/MtfTests']
                : 'basic';

            $configData = $objectManagerFactory->getObjectManager()->create('Magento\Mtf\Config\TestRunner');
            $configData->setFileName($configFileName . '.xml')->load();

            $this->objectManager = $objectManagerFactory->create(
                ['Magento\Mtf\Config\TestRunner' => $configData]
            );
        }
    }
}
