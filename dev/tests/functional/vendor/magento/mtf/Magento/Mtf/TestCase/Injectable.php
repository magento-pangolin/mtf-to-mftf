<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\TestCase;

use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Class is extended from Functional and is a base test case class for functional testing.
 *
 * @api
 * @abstract
 */
abstract class Injectable extends Functional
{
    /**
     * Test case full name.
     *
     * @var string
     */
    protected $dataId;

    /**
     * Variation identifier.
     *
     * @var string
     */
    protected $variationName;

    /**
     * Test case file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Abstract Constraint instance.
     *
     * @var AbstractConstraint
     */
    protected $constraint;

    /**
     * Array with shared arguments between variations for non-sharable objects.
     *
     * @var array
     */
    protected static $sharedArguments = [];

    /**
     * Array with local test run arguments.
     *
     * @var array
     */
    protected $localArguments = [];

    /**
     * Current variation data.
     *
     * @var array
     */
    protected $currentVariation = [];

    /**
     * Number of variation restart attempts.
     *
     * @var int
     */
    protected $rerunCount;

    /**
     * Constructs a test case with the given name.
     *
     * @constructor
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     * @param string $path
     */
    public function __construct($name = null, array $data = [], $dataName = '', $path = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->dataId = get_class($this) . '::' . $name;
        $this->filePath = $path;
        $this->rerunCount = empty($_ENV['rerun_count']) ? 0 : $_ENV['rerun_count'];
    }

    /**
     * Get file path.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Set variation name.
     *
     * @param string $variationName
     * @return void
     */
    public function setVariationName($variationName)
    {
        $this->variationName = $variationName;
    }

    /**
     * Get variation name.
     *
     * @return string
     */
    public function getVariationName()
    {
        return $this->variationName;
    }

    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Run with Variations Iterator.
     *
     * @param \PHPUnit\Framework\TestResult $result
     * @return \PHPUnit\Framework\TestResult
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function run(\PHPUnit\Framework\TestResult $result = null)
    {
        $this->eventManager->dispatchEvent(['execution'], ['[start test case execution]']);
        if ($this->isParallelRun) {
            return parent::run($result);
        }
        try {
            \PHP_Timer::start();
            if (!isset(static::$sharedArguments[$this->dataId]) && method_exists($this, '__prepare')) {
                static::$sharedArguments[$this->dataId] = (array) $this->getObjectManager()->invoke($this, '__prepare');
            }
            /** @var $testVariationIterator \Magento\Mtf\Util\Iterator\TestCaseVariation */
            $testVariationIterator = $this->getObjectManager()->create(
                \Magento\Mtf\Util\Iterator\TestCaseVariation::class,
                ['testCase' => $this]
            );
            while ($testVariationIterator->valid()) {
                if (method_exists($this, '__inject')) {
                    $this->localArguments = $this->getObjectManager()->invoke(
                        $this,
                        '__inject',
                        isset(self::$sharedArguments[$this->dataId]) ? self::$sharedArguments[$this->dataId] : []
                    );
                    if (!$this->localArguments || !is_array($this->localArguments)) {
                        $this->localArguments = [];
                    }
                }
                if (isset(static::$sharedArguments[$this->dataId])) {
                    $this->localArguments = array_merge(static::$sharedArguments[$this->dataId], $this->localArguments);
                }
                $this->currentVariation = $testVariationIterator->current();
                $variation = $this->prepareVariation(
                    $this->currentVariation,
                    $this->localArguments
                );






                // GATHER SOME DATA
                $variationName = explode("_", $this->getVariationName())[0];
                list($_, $moduleName) = explode("\\", $this->dataId);
                $data = $this->objectManager->get('Magento\Mtf\Variation\Config\Data');
                $truncatedDataId = str_replace("::test", "", $this->dataId);
                $truncatedDataId = str_replace("::", "", $truncatedDataId);
                $testCaseData = $data->get('testCase')[$truncatedDataId];
//                $variationData = $testCaseData["variation"][$variationName];
//                $title = $variationData["summary"] ?? $testCaseData["summary"] ?? "";
//                $testCaseId = $variationData["ticketId"] ?? $testCaseData["ticketId"] ?? "";

                // new MftfTestGenerator is instantiated per variation
                $driver = $this->getObjectManager()->get('Magento\Mtf\Driver');
//                $driver->MFTF_TEST_GENERATOR = new \Magento\Mtf\MftfGenerator($variationName, $moduleName, $title, $testCaseId);

// commented out to make room for CSV output instead
//                $this->executeTestVariation($result, $variation);
//
//                // Generate after test run to dump the resulting xml to the filesystem
//                $driver->MFTF_TEST_GENERATOR->generateTest();

                $moduleToPriorityMap = [
                    "AdvancedCheckout" => "P1",
                    "AdvancedPricingImportExport" => "P1",
                    "Analytics" => "P1",
                    "Authorizenet" => "P1",
                    "Braintree" => "P1",
                    "Catalog" => "P1",
                    "CatalogImportExport" => "P1",
                    "CatalogRule" => "P1",
                    "CatalogRuleConfigurable" => "P1",
                    "CatalogSearch" => "P1",
                    "CatalogStaging" => "P1",
                    "CatalogUrlRewrite" => "P1",
                    "Checkout" => "P1",
                    "Cms" => "P1",
                    "Company" => "P1",
                    "CompanyCredit" => "P1",
                    "Config" => "P1",
                    "ConfigurableProduct" => "P1",
                    "Customer" => "P1",
                    "Cybersource" => "P1",
                    "Eway" => "P1",
                    "ImportExport" => "P1",
                    "Indexer" => "P1",
                    "LayeredNavigation" => "P1",
                    "NegotiableQuote" => "P1",
                    "NegotiableQuoteSharedCatalog" => "P1",
                    "PageCache" => "P1",
                    "Payment" => "P1",
                    "Paypal" => "P1",
                    "QuickOrder" => "P1",
                    "Sales" => "P1",
                    "SalesRule" => "P1",
                    "Search" => "P1",
                    "Security" => "P1",
                    "Setup" => "P1",
                    "SharedCatalog" => "P1",
                    "Shipping" => "P1",
                    "Store" => "P1",
                    "Swatches" => "P1",
                    "TargetRule" => "P1",
                    "Tax" => "P1",
                    "Ui" => "P1",
                    "UrlRewrite" => "P1",
                    "VisualMerchandiser" => "P1",
                    "Worldpay" => "P1",
                    "Backend" => "P2",
                    "Banner" => "P2",
                    "Bundle" => "P2",
                    "Captcha" => "P2",
                    "CatalogPermissions" => "P2",
                    "CompanyPayment" => "P2",
                    "CurrencySymbol" => "P2",
                    "CustomerBalance" => "P2",
                    "CustomerCustomAttributes" => "P2",
                    "CustomerFinance" => "P2",
                    "CustomerImportExport" => "P2",
                    "CustomerSegment" => "P2",
                    "Email" => "P2",
                    "GiftWrapping" => "P2",
                    "GoogleTagManager" => "P2",
                    "GroupedProduct" => "P2",
                    "Integration" => "P2",
                    "Logging" => "P2",
                    "Persistent" => "P2",
                    "ProductVideo" => "P2",
                    "Reports" => "P2",
                    "RequisitionList" => "P2",
                    "Review" => "P2",
                    "Reward" => "P2",
                    "Rma" => "P2",
                    "SampleData" => "P2",
                    "Signifyd" => "P2",
                    "Sitemap" => "P2",
                    "Support" => "P2",
                    "User" => "P2",
                    "Vault" => "P2",
                    "VersionsCms" => "P2",
                    "Weee" => "P2",
                    "Widget" => "P2",
                    "Wishlist" => "P2",
                    "CatalogEvent" => "P3",
                    "CheckoutAgreements" => "P3",
                    "Directory" => "P3",
                    "Downloadable" => "P3",
                    "GiftCard" => "P3",
                    "GiftCardAccount" => "P3",
                    "GiftMessage" => "P3",
                    "GiftRegistry" => "P3",
                    "Invitation" => "P3",
                    "Msrp" => "P3",
                    "MultipleWishlist" => "P3",
                    "Newsletter" => "P3",
                    "Reminder" => "P3",
                    "SalesArchive" => "P3",
                    "Swagger" => "P3",
                    "Variable" => "P3"
                ];

                $jiraIssueType = "Story";
//                $testName = explode("\\", $truncatedDataId)[4];
//                if (!$this->endsWith($testName, "Test")) {
//                    $testName = explode("\\", $truncatedDataId)[5];
//                }
                preg_match("/\w+Test/", $truncatedDataId, $matches);
                $testName = $matches[0];
                $jiraSummary = "Convert " . $testName . " to MFTF";
                $jiraComponents = "Module/ " . $moduleName;
                $jiraPriority = $moduleToPriorityMap[$moduleName];

                if (defined(get_class($this) . "::SEVERITY")) {
                    $jiraSeverity = $this::SEVERITY;
                } elseif (defined(get_class($this) . "::MVP") && $this::MVP == "yes") {
                    $jiraSeverity = "S1";
                } else {
                    $jiraSeverity = "S3";
                }

                $jiraLabels = $jiraSeverity . " mtf-to-mftf";

                $out = fopen('php://output', 'w');
                fputcsv($out, array($jiraIssueType, $jiraSummary, $jiraComponents, $jiraPriority, $jiraLabels));
                fclose($out);







                if ($this->rerunCount > 0 && $this->getStatus() != \PHPUnit\Runner\BaseTestRunner::STATUS_PASSED) {
                    $this->rerunCount -= 1;
                } else {
                    $this->rerunCount = empty($_ENV['rerun_count']) ? 0 : $_ENV['rerun_count'];
                    $testVariationIterator->next();
                }
                $this->localArguments = [];
            }
        } catch (\PHPUnit\Framework\IncompleteTestError $phpUnitException) {
            $result->addError($this, $phpUnitException, \PHP_Timer::stop());
        } catch (\PHPUnit\Framework\AssertionFailedError $phpUnitException) {
            $this->eventManager->dispatchEvent(['failure'], [$phpUnitException->getMessage()]);
            $result->addFailure($this, $phpUnitException, \PHP_Timer::stop());
        } catch (\Exception $exception) {
            $this->eventManager->dispatchEvent(['exception'], [$exception->getMessage()]);
            $result->addError($this, $exception, \PHP_Timer::stop());
        }
        self::$sharedArguments = [];

        return $result;
    }

    /**
     * Execute test variation.
     *
     * @param \PHPUnit\Framework\TestResult $result
     * @param array $variation
     * @return void
     */
    protected function executeTestVariation(\PHPUnit\Framework\TestResult $result, array $variation)
    {
        $this->eventManager->dispatchEvent(['execution'], ['[start variation execution]']);
        // remove constraint object from previous test case variation iteration
        $this->constraint = null;
        $arguments = isset($variation['arguments'])
            ? $variation['arguments']
            : [];
        $this->setDependencyInput($arguments);

        if (isset($variation['constraint'])) {
            $this->constraint = $variation['constraint'];
            $this->localArguments = array_merge($arguments, $this->localArguments);
        }
        parent::run($result);
    }

    /**
     * Override to run attached constraint if available.
     *
     * @return mixed
     */
    protected function runTest()
    {
        if (isset($this->currentVariation['arguments']['issue'])
            && !empty($this->currentVariation['arguments']['issue'])
        ) {
            $this->rerunCount = 0;
            $this->markTestIncomplete($this->currentVariation['arguments']['issue']);
        }
        $testResult = parent::runTest();
        $this->localArguments = array_merge($this->localArguments, is_array($testResult) ? $testResult : []);
        $arguments = array_merge($this->currentVariation['arguments'], $this->localArguments);
        if ($this->constraint) {
            $this->constraint->configure($arguments);
            self::assertThat($this->getName(), $this->constraint);
        }

        return $testResult;
    }

    /**
     * Gets the data set description of a TestCase.
     *
     * @param  boolean $includeData
     * @return string
     */
    public function getDataSetAsString($includeData = true)
    {
        $buffer = '';

        if (isset($this->variationName)) {
            if (!empty($this->variationName)) {
                if (is_int($this->variationName)) {
                    $buffer .= sprintf(' with data set #%d', $this->variationName);
                } else {
                    $buffer .= sprintf(' with data set "%s"', $this->variationName);
                }
            }
            if ($includeData) {
                $buffer .= sprintf(' (%s)', $this->variationName);
            }
        } else {
            $buffer = parent::getDataSetAsString($includeData);
        }

        return $buffer;
    }

    /**
     * Prepare variation for Test Case Method.
     *
     * @param array $variation
     * @param array $arguments
     * @return array
     */
    protected function prepareVariation(array $variation, array $arguments)
    {
        if (isset($variation['arguments'])) {
            $arguments = array_merge($variation['arguments'], $arguments);
        }
        if (isset($variation['arguments']['variation_name'])) {
            $this->setVariationName($variation['arguments']['variation_name'] . "_" . $this->rerunCount);
        } else {
            $this->setVariationName($variation['id'] . "_" . $this->rerunCount);
        }
        $resolvedArguments = $this->getObjectManager()
            ->prepareArguments($this, $this->getName(false), $arguments);

        if (isset($arguments['constraint'])) {
            $parameters = $this->getObjectManager()->getParameters($this, $this->getName(false));
            $preparedConstraint = $this->prepareConstraintObject($arguments['constraint']);

            if (isset($parameters['constraint'])) {
                $resolvedArguments['constraint'] = $preparedConstraint;
            } else {
                $variation['constraint'] = $preparedConstraint;
            }
        }

        $variation['arguments'] = $resolvedArguments;

        return $variation;
    }

    /**
     * Prepare configuration object.
     *
     * @param array $constraints
     * @return \Magento\Mtf\Constraint\Composite
     */
    protected function prepareConstraintObject(array $constraints)
    {
        /** @var \Magento\Mtf\Util\SequencesSorter $sorter */
        $sorter = $this->getObjectManager()->create(\Magento\Mtf\Util\SequencesSorter::class);
        $constraintsArray = $sorter->sort($constraints);
        return $this->getObjectManager()->create(
            \Magento\Mtf\Constraint\Composite::class,
            ['codeConstraints' => array_keys($constraintsArray)]
        );
    }
}
