<?php

namespace Magento\Mtf;

class MftfGenerator
{
    const OUTPUT_DIR = "/Users/treece/dev/magento2ce/dev/tests/acceptance/tests/functional/Magento/FunctionalTest/_mtf/";

    public $testName;
    public $moduleName;
    public $title;
    public $testCaseId;
    public $testTemplate;
    public $stepCounter = 0;
    public $testActions = [];

    public function __construct($testName = null, $moduleName = null, $title = null, $testCaseId = null)
    {
        $this->testName = $testName;
        $this->moduleName = $moduleName;
        $this->title = $title;
        $this->testCaseId = $testCaseId;
        $this->testTemplate = file_get_contents(__DIR__ . '/TestTemplate.xml');
    }

    public function addAction($action)
    {
        $this->testActions[] = str_replace("%%StepKey%%", "stepNumber" . $this->stepCounter, $action);
        $this->stepCounter += 1;
    }

    public function addReviewAssertComment()
    {
        $this->testActions[] = "<!-- ASSERT MUST BE REVIEWED MANUALLY -->";
    }

    public function generateTest()
    {
        $actions = implode(PHP_EOL . '        ' , $this->testActions);
        $test = str_replace("%%TestActions%%", $actions, $this->testTemplate);

        // Generate <waitForPageLoad> in place of known MTF waits
        $test = str_replace("<seeElement selector=\"//*[@data-role = 'spinner']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@id = 'container']/descendant-or-self::*/*[@data-role = 'spinner']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@data-role = 'loader']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@id = 'loading-mask']/descendant-or-self::*/*[@id = 'loading_mask_loader']\"", "<waitForPageLoad", $test);

        // Generate annotations
        $test = str_replace("%%TestName%%", $this->testName, $test);
        $test = str_replace("%%TestFeatures%%", $this->moduleName, $test);
        $test = str_replace("%%TestTitle%%", $this->title, $test);
        $test = str_replace("%%TestDescription%%", $this->title, $test);
        $test = str_replace("%%TestCaseId%%", $this->testCaseId, $test);

        // todo other annotations
        // todo webabi actions
        // todo php asserts

        $destinationDir = self::OUTPUT_DIR . $this->moduleName . "/Test";
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }

        file_put_contents($destinationDir . "/" . $this->testName . ".xml", $test);
    }
}
