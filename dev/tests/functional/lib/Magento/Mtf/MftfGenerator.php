<?php

namespace Magento\Mtf;

class MftfGenerator
{
    public $testName;
    public $testTemplate;
    public $stepCounter = 0;
    public $testActions = [];


    public function __construct($testName)
    {
        $this->testName = $testName;
        $this->testTemplate = file_get_contents(__DIR__ . '/TestTemplate.xml');
    }

    public function addAction($action)
    {
        $this->testActions[] = str_replace("%%StepKey%%", "stepNumber" . $this->stepCounter, $action);
        $this->stepCounter += 1;
    }

    public function generateTest()
    {
        $actions = implode(PHP_EOL . '        ' , $this->testActions);
        $test = str_replace("%%TestActions%%", $actions, $this->testTemplate);

        $test = str_replace("<seeElement selector=\"//*[@data-role = 'spinner']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@id = 'container']/descendant-or-self::*/*[@data-role = 'spinner']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@data-role = 'loader']\"", "<waitForPageLoad", $test);
        $test = str_replace("<seeElement selector=\"//*[@id = 'loading-mask']/descendant-or-self::*/*[@id = 'loading_mask_loader']\"", "<waitForPageLoad", $test);



        file_put_contents(__DIR__ . '/TestBla.xml', $test);
    }
}
