<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Client\Driver\Facebook;

use Magento\Mtf\Config\DataInterface;
use Magento\Mtf\ObjectManager;
use Magento\Mtf\Client\Locator;
use Magento\Mtf\Client\DriverInterface;
use Magento\Mtf\Client\ElementInterface;
use Magento\Mtf\System\Event\EventManagerInterface;

/**
 * Class Driver
 */
final class Driver implements DriverInterface
{
    /**
     * Remote driver
     *
     * @var RemoteDriver
     */
    protected $driver;

    /**
     * Configuration for driver
     *
     * @var DataInterface
     */
    protected $configuration;

    /**
     * Event manager to manage events
     *
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * Application object manager
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Tracker variable for the current browser page
     *
     * @var string
     */
    protected static $previousUrl = '';

    /**
     * Tracker variable for the active jQuery ajax count
     *
     * @var int
     */
    protected static $previousJqAjax = 0;

    /**
     * Test metadata variable to track sequential readiness check failures for the same jquery ajax request count
     * Value is reset to 0 when the current page url changes
     *
     * @var int
     */
    protected static $jqAjaxFailures = 0;

    /**
     * Tracker variable for the active prototype.js ajax count
     *
     * @var int
     */
    protected static $previousPrototypeAjax = 0;


    /**
     * Test metadata variable to track sequential readiness check failures for the same prototype.js ajax request count
     * Value is reset to 0 when the current page url changes
     *
     * @var int
     */
    protected static $prototypeAjaxFailures = 0;

    /**
     * Constructor
     *
     * @param RemoteDriver $driver
     * @param EventManagerInterface $eventManager
     * @param DataInterface $configuration
     * @param ObjectManager $objectManager
     */
    public function __construct(
        RemoteDriver $driver,
        EventManagerInterface $eventManager,
        DataInterface $configuration,
        ObjectManager $objectManager
    ) {
        $this->driver = $driver;
        $this->configuration = $configuration;
        $this->eventManager = $eventManager;
        $this->objectManager = $objectManager;

        $this->init();
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->closeWindow();
    }

    /**
     * Initialize driver
     *
     * @return void
     */
    protected function init()
    {
        $this->driver->get('about:blank');
        $this->driver->manage()->deleteAllCookies();
        $this->driver->manage()->window()->maximize();
        $this->driver->navigate()->refresh();
    }

    /**
     * Get native element
     *
     * @param Locator $locator
     * @param \RemoteWebElement $context
     * @param bool $wait
     * @return \RemoteWebElement
     */
    protected function getElement(Locator $locator, \RemoteWebElement $context = null, $wait = true)
    {
        $context = $context === null
            ? $this->driver
            : $context;

        $criteria = $this->getSearchCriteria($locator);

        if ($wait) {
            return $this->waitUntil(
                function () use ($context, $criteria) {
                    $element = $context->findElement($criteria);
                    return $element->isDisplayed() ? $element : null;
                }
            );
        }

        $this->waitForPageToLoad();

        return $context->findElement($criteria);
    }

    /**
     * @param ElementInterface $element
     * @param bool $wait
     * @return \RemoteWebElement
     * @throws \Exception
     */
    protected function getNativeElement(ElementInterface $element, $wait = true)
    {
        $chainElements = [$element];
        while($element = $element->getContext()) {
            $chainElements[] = $element;
        }

        $contextElement = null;
        /** @var ElementInterface $context */
        foreach (array_reverse($chainElements) as $chainElement) {
            /** @var ElementInterface $chainElement */
            try {
                // First call "getElement" with $resultElement equal "null" value
                $contextElement = $this->getElement($chainElement->getLocator(), $contextElement, $wait);
            } catch (\Exception $e) {
                throw new \Exception (
                    sprintf('Error occurred on attempt to get element. Message: "%s". Locator: "%s" . Wait: "%s"',
                        $e->getMessage(),
                        $chainElement->getAbsoluteSelector(),
                        $wait
                    )
                );
            }
        }

        return $contextElement;
    }

    /**
     * Get search criteria
     *
     * @param Locator $locator
     * @return \WebDriverBy
     */
    public function getSearchCriteria(Locator $locator)
    {
        $value = $locator['value'];
        switch ($locator['using']) {
            case Locator::SELECTOR_XPATH:
                return \WebDriverBy::xpath($value);
            case Locator::SELECTOR_ID:
                return \WebDriverBy::id($value);
            case Locator::SELECTOR_NAME:
                return \WebDriverBy::name($value);
            case Locator::SELECTOR_CLASS_NAME:
                return \WebDriverBy::className($value);
            case Locator::SELECTOR_TAG_NAME:
                return \WebDriverBy::tagName($value);
            case Locator::SELECTOR_LINK_TEXT:
                return \WebDriverBy::linkText($value);
            case Locator::SELECTOR_CSS:
            default:
                return \WebDriverBy::cssSelector($value);
        }
    }

    /**
     * Click
     *
     * @param ElementInterface $element
     * @return void
     */
    public function click(ElementInterface $element)
    {
        $this->eventManager->dispatchEvent(['click_before'], [__METHOD__, $element->getAbsoluteSelector()]);
        $this->getNativeElement($element)->click();
        $this->eventManager->dispatchEvent(['click_after'], [__METHOD__, $element->getAbsoluteSelector()]);
    }

    /**
     * Double click
     *
     * @param ElementInterface $element
     * @return void
     */
    public function doubleClick(ElementInterface $element)
    {
        $this->driver->action()
            ->doubleClick($this->getNativeElement($element))
            ->perform();
    }

    /**
     * Right click
     *
     * @param ElementInterface $element
     * @return void
     * @throws \Exception
     */
    public function rightClick(ElementInterface $element)
    {
        throw new \Exception('To use this action, extend the native driver!');
    }

    /**
     * Check whether element is present in the DOM.
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isPresent(ElementInterface $element)
    {
        $isPresent = true;
        $nativeElement = null;
        try {
            $this->eventManager->dispatchEvent(['is_present'], [__METHOD__, $element->getAbsoluteSelector()]);
            $nativeElement = $this->getNativeElement($element, false);
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            $isPresent = false;
        }

        return $nativeElement !== null && $isPresent;
    }

    /**
     * Check whether element is visible
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isVisible(ElementInterface $element)
    {
        try {
            $visible = $this->getNativeElement($element, false)->isDisplayed();
        } catch (\Exception $e) {
            $visible = false;
        }

        return $visible;
    }

    /**
     * Check whether element is enabled
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isDisabled(ElementInterface $element)
    {
        return !$this->getNativeElement($element)->isEnabled();
    }

    /**
     * Check whether element is selected
     *
     * @param ElementInterface $element
     * @return bool
     */
    public function isSelected(ElementInterface $element)
    {
        return $this->getNativeElement($element, false)->isSelected();
    }

    /**
     * Set the value
     *
     * @param ElementInterface $element
     * @param string|array $value
     * @return void
     */
    public function setValue(ElementInterface $element, $value)
    {
        $wrappedElement = $this->getNativeElement($element);
        $wrappedElement->clear();
        $this->focus($element);
        $wrappedElement->sendKeys($value);
    }

    /**
     * Get the value
     *
     * @param ElementInterface $element
     * @return null|string
     */
    public function getValue(ElementInterface $element)
    {
        return $this->getNativeElement($element)
            ->getAttribute('value');
    }

    /**
     * Get content
     *
     * @param ElementInterface $element
     * @return string
     */
    public function getText(ElementInterface $element)
    {
        return $this->getNativeElement($element)->getText();
    }

    /**
     * Find element on the page
     *
     * @param string $selector
     * @param string $strategy
     * @param string $type = select|multiselect|checkbox|null OR custom class with full namespace
     * @param ElementInterface $context
     * @return ElementInterface
     * @throws \Exception
     */
    public function find(
        $selector,
        $strategy = Locator::SELECTOR_CSS,
        $type = null,
        ElementInterface $context = null
    ) {
        $this->eventManager->dispatchEvent(['find'], [__METHOD__, sprintf('(%s -> %s)', $strategy, $selector)]);

        $locator = $this->objectManager->create(
            'Magento\Mtf\Client\Locator',
            [
                'value' => $selector,
                'strategy' => $strategy
            ]
        );

        $className = 'Magento\Mtf\Client\ElementInterface';
        if (null !== $type) {
            if (strpos($type, '\\') === false) {
                $type = ucfirst(strtolower($type));
                if (class_exists('Magento\Mtf\Client\Element\\' . $type . 'Element')) {
                    $className = 'Magento\Mtf\Client\Element\\' . $type . 'Element';
                }
            } else {
                if (!class_exists($type) && !interface_exists($type)) {
                    throw new \Exception('Requested interface or class does not exists!');
                }
                $className = $type;
            }
        }

        return $this->objectManager->create(
            $className,
            [
                'driver' => $this,
                'locator' => $locator,
                'context' => $context
            ]
        );
    }

    /**
     * Drag and drop element to(between) another element(s)
     *
     * @param ElementInterface $element
     * @param ElementInterface $target
     * @return void
     */
    public function dragAndDrop(ElementInterface $element, ElementInterface $target)
    {
        $this->driver->action()
            ->dragAndDrop(
                $this->getNativeElement($element),
                $this->getNativeElement($target)
            )->perform();
    }

    /**
     * Hover mouse over an element.
     *
     * @param ElementInterface $element
     * @return void
     */
    public function hover(ElementInterface $element)
    {
        $this->driver->action()
            ->moveToElement($this->getNativeElement($element))
            ->perform();
    }

    /**
     * Send a sequence of key strokes to the active element.
     *
     * @param ElementInterface $element
     * @param array $keys
     * @return void
     */
    public function keys(ElementInterface $element, array $keys)
    {
        $this->focus($element);
        $this->getNativeElement($element)->sendKeys($keys);
    }

    /**
     * Wait until callback isn't null or timeout occurs
     *
     * @param callable $callback
     * @return mixed
     */
    public function waitUntil($callback)
    {
        return $this->driver->wait()->until($callback);
    }

    /**
     * Get all elements by locator
     *
     * @param ElementInterface $context
     * @param string $selector
     * @param string $strategy
     * @param null|string $type
     * @param bool $wait
     * @return ElementInterface[]
     * @throws \Exception
     */
    public function getElements(
        ElementInterface $context,
        $selector,
        $strategy = Locator::SELECTOR_CSS,
        $type = null,
        $wait = true
    ) {
        $locator = new Locator($selector, $strategy);
        $resultElements = [];
        $nativeContext = $this->getNativeElement($context, $wait);
        $criteria = $this->getSearchCriteria($locator);

        if ($wait) {
            try {
                $nativeElements = $this->waitUntil(
                    function() use ($nativeContext, $criteria) {
                        return $nativeContext->findElements($criteria);
                    }
                );
            } catch (\Exception $e) {
                throw new \Exception(
                    sprintf(
                        'Error occurred during waiting for an elements. Message: "%s". Locator: "%s"',
                        $e->getMessage(),
                        $context->getAbsoluteSelector() . ' -> ' . $locator
                    )
                );
            }
        } else {
            $this->waitForPageToLoad();
            $nativeElements = $nativeContext->findElements($this->getSearchCriteria($locator));
        }

        foreach ($nativeElements as $key => $element) {
            $resultElements[] = $this->find(
                $this->getRelativeXpath($element, $nativeContext),
                Locator::SELECTOR_XPATH,
                $type,
                $context
            );
        }

        return $resultElements;
    }

    /**
     * Retrieve relative xpath from context to element
     *
     * @param \RemoteWebElement $element
     * @param \RemoteWebElement $context
     * @param string $path
     * @param bool $includeLastIndex
     * @return null
     */
    protected function getRelativeXpath(
        \RemoteWebElement $element,
        \RemoteWebElement $context,
        $path = '',
        $includeLastIndex = true
    ) {
        if($element->equals($context)) {
            return '.' . $path;
        }

        $parentLocator = new Locator('..', Locator::SELECTOR_XPATH);
        $parentElement = $element->findElement($this->getSearchCriteria($parentLocator));

        $childrenLocator = new Locator('*', Locator::SELECTOR_XPATH);

        $index = 1;
        $tag = $element->getTagName();
        if (!$includeLastIndex) {
            return $this->getRelativeXpath($parentElement, $context, '/' . $tag);
        }
        foreach ($parentElement->findElements($this->getSearchCriteria($childrenLocator)) as $child) {
            /** @var \RemoteWebElement $child */
            if ($child->equals($element)) {
                return $this->getRelativeXpath($parentElement, $context, '/' . $tag . '[' . $index . ']' . $path);
            }
            if ($child->getTagName() == $tag) {
                ++$index;
            }
        }
        return null;
    }

    /**
     * Get the value of a the given attribute of the element
     *
     * @param ElementInterface $element
     * @param string $name
     * @return string
     */
    public function getAttribute(ElementInterface $element, $name)
    {
        return $this->getNativeElement($element)
            ->getAttribute($name);
    }

    /**
     * Open page
     *
     * @param string $url
     * @return void
     */
    public function open($url)
    {
        $this->eventManager->dispatchEvent(['open_before'], [__METHOD__, $url]);
        $this->driver->get($url);
        $this->eventManager->dispatchEvent(['open_after'], [__METHOD__, $url]);
    }

    /**
     * Back to previous page
     *
     * @return void
     */
    public function back()
    {
        $this->eventManager->dispatchEvent(['back'], [__METHOD__]);
        $this->driver->navigate()->back();
    }

    /**
     * Forward page
     *
     * @return void
     */
    public function forward()
    {
        $this->eventManager->dispatchEvent(['forward'], [__METHOD__]);
        $this->driver->navigate()->forward();
    }

    /**
     * Refresh page
     *
     * @return void
     */
    public function refresh()
    {
        $this->eventManager->dispatchEvent(['refresh'], [__METHOD__]);
        $this->driver->navigate()->refresh();
    }

    /**
     * Reopen browser
     *
     * @return void
     */
    public function reopen()
    {
        $this->eventManager->dispatchEvent(['reopen'], [__METHOD__]);
        $this->closeWindow();
        $this->driver->createNewSession();
        $this->init();
    }

    /**
     * Change the focus to a frame in the page by locator
     *
     * @param Locator|null $locator
     * @return void
     */
    public function switchToFrame(Locator $locator = null)
    {
        if (null === $locator) {
            $this->eventManager->dispatchEvent(['switch_to_frame'], [(string) $locator]);
            $this->driver->switchTo()->frame($this->getElement($locator));
        } else {
            $this->eventManager->dispatchEvent(['switch_to_frame'], ['Switch to main window']);
            $this->driver->switchTo()->frame();
        }
    }

    /**
     * Open new tab/window in Browser.
     *
     * @return void
     */
    public function openWindow()
    {
        $this->driver->createNewSession();
    }

    /**
     * Close the current window or specified one.
     *
     * @param string|null $handle [optional]
     * @return void
     */
    public function closeWindow($handle = null)
    {
        $windowHandles = $this->driver->getWindowHandles();
        if (count($windowHandles) > 1) {
            $windowHandle = $handle !== null ? $handle : end($windowHandles);
            $this->driver->switchTo()->window($windowHandle);
            $this->driver->quit();
            $this->driver->switchTo()->window(reset($windowHandles));
        } else {
            $this->driver->quit();
        }
    }

    /**
     * Changes the focus to the specified window or to the latest one.
     *
     * @param string|null $handle [optional]
     * @return void
     */
    public function selectWindow($handle = null)
    {
        $windowHandles = $this->driver->getWindowHandles();
        $windowHandle = $handle !== null ? $handle : end($windowHandles);
        $this->driver->switchTo()->window($windowHandle);
    }

    /**
     * Retrieves the current window handle.
     *
     * @return string
     */
    public function getCurrentWindow()
    {
        return $this->driver->getWindowHandle();
    }

    /**
     * Retrieves a list of all available window handles.
     *
     * @return array
     */
    public function getWindowHandles()
    {
        return $this->driver->getWindowHandles();
    }

    /**
     * Get page title text.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->driver->getTitle();
    }

    /**
     * Press OK on an alert, or confirms a dialog
     *
     * @return void
     */
    public function acceptAlert()
    {
        $this->driver->switchTo()->alert()->accept();
        $this->eventManager->dispatchEvent(['accept_alert_after'], [__METHOD__]);
    }

    /**
     * Press Cancel on an alert, or does not confirm a dialog
     *
     * @return void
     */
    public function dismissAlert()
    {
        $this->driver->switchTo()->alert()->dismiss();
        $this->eventManager->dispatchEvent(['dismiss_alert_after'], [__METHOD__]);
    }

    /**
     * Get the alert dialog text
     *
     * @return string
     */
    public function getAlertText()
    {
        $this->eventManager->dispatchEvent(['get_alert_text'], [__METHOD__]);
        return $this->driver->switchTo()->alert()->getText();
    }

    /**
     * Set the text to a prompt popup
     *
     * @param string $text
     * @return void
     */
    public function setAlertText($text)
    {
        $this->driver->switchTo()->alert()->sendKeys($text);
    }

    /**
     * Get current page url
     *
     * @return string
     */
    public function getUrl()
    {
        try {
            if ($this->driver->switchTo()->alert()->getText()) {
                return null;
            }
        } catch (\Exception $exception) {
            return $this->driver->getCurrentURL();
        }

        return $this->driver->getCurrentURL();
    }

    /**
     * Get Html page source
     *
     * @return string
     */
    public function getHtmlSource()
    {
        return $this->driver->getPageSource();
    }

    /**
     * Get binary string of image
     *
     * @return string
     */
    public function getScreenshotData()
    {
        return $this->driver->takeScreenshot();
    }

    /**
     * Inject Js Error collector
     *
     * @return void
     */
    public function injectJsErrorCollector()
    {
        $script = '
            window.onerror = function(msg, url, line) {
                var errors = {};
                if (localStorage.getItem("errorsHistory")) {
                    errors = JSON.parse(localStorage.getItem("errorsHistory"));
                }
                if (!(window.location.href in errors)) {
                    errors[window.location.href] = [];
                }
                errors[window.location.href].push("error: \'" + msg + "\' " + "file: " + url + " " + "line: " + line);
                localStorage.setItem("errorsHistory", JSON.stringify(errors));
            }
        ';

        $this->driver->executeScript($script, []);
    }

    /**
     * Get js errors
     *
     * @return string[]
     */
    public function getJsErrors()
    {
        $script = '
            errors = JSON.parse(localStorage.getItem("errorsHistory"));
            localStorage.removeItem("errorsHistory");
            return errors;
        ';

        return $this->driver->executeScript($script, []);
    }

    /**
     * @throws \Exception
     */
    public function waitForPageToLoad()
    {
        $driver = $this->driver;

        try {
            $this->waitUntil(function() use ($driver) {
                // document.readyState
                $readyState = $driver->executeScript("return document['readyState']", []);
                return $readyState === 'complete' || $readyState === 'uninitialized';
            });
        }
        catch (\Exception $e) {
            throw new \Exception(
                sprintf('Error occurred during waiting for document readyState. Message: "%s"', $e->getMessage())
            );
        }

        $url = $driver->getCurrentURL();
        if ($url != Driver::$previousUrl) {
            Driver::$previousJqAjax = 0;
            Driver::$jqAjaxFailures = 0;
            Driver::$previousPrototypeAjax = 0;
            Driver::$prototypeAjaxFailures = 0;
            Driver::$previousUrl = $url;
        }

        try {
            $this->waitUntil([$this, 'isPageReady']);
            Driver::$previousJqAjax = 0;
            Driver::$jqAjaxFailures = 0;
            Driver::$previousPrototypeAjax = 0;
            Driver::$prototypeAjaxFailures = 0;
        } catch (\Exception $e) {
            $failsBeforeReset = isset($_ENV['readiness_failure_threshold']) ? $_ENV['readiness_failure_threshold'] : 3;

            // Check if jQuery ajax count failed on the same value, which can happen if an exception is
            // thrown during an ajax callback causing the active request count to not decrement
            $jqAjax = intval($driver->executeScript(
                'if (!!window.jQuery) {
                    return window.jQuery.active;
                }
                return 0;',
                []
            ));

            if ($jqAjax == Driver::$previousJqAjax) {
                Driver::$jqAjaxFailures++;
            }
            else {
                Driver::$jqAjaxFailures = 1;
                Driver::$previousJqAjax = $jqAjax;
            }

            if (Driver::$jqAjaxFailures >= $failsBeforeReset) {
                $driver->executeScript("if (!!window.jQuery) { window.jQuery.active = 0; }", []);
                Driver::$jqAjaxFailures = 0;
            }

            // Check if prototype.js ajax count failed on the same value, which can happen if an exception is
            // thrown during an ajax callback causing the active request count to not decrement
            $prototypeAjax = intval($driver->executeScript(
                'if (!!window.Prototype) {
                    return window.Ajax.activeRequestCount;
                }
                return 0;',
                []
            ));

            if ($prototypeAjax == Driver::$previousPrototypeAjax) {
                Driver::$prototypeAjaxFailures++;
            }
            else {
                Driver::$prototypeAjaxFailures = 1;
                Driver::$previousPrototypeAjax = $prototypeAjax;
            }

            if (Driver::$prototypeAjaxFailures >= $failsBeforeReset) {
                $driver->executeScript("if (!!window.Prototype) { window.Ajax.activeRequestCount = 0; }", []);
                Driver::$prototypeAjaxFailures = 0;
            }
        }
    }

    /**
     * Checks active ajax requests and require.js module registry queue to see if the page is ready
     *
     * @return bool
     */
    private function isPageReady() {
        $ready = true;
        $driver = $this->driver;

        // jQuery ajax requests
        $jqAjax = intval($driver->executeScript([
            'if (!!window.jQuery) {
                    return window.jQuery.active;
                }
                return 0;',
            []
        ]));
        if ($jqAjax > 0) {
            $ready = false;
        }
        else {
            Driver::$previousJqAjax = 0;
            Driver::$jqAjaxFailures = 0;
        }

        // prototype.js ajax requests
        $prototypeAjax = intval($driver->executeScript(
            'if (!!window.Prototype) {
                    return window.Ajax.activeRequestCount;
                }
                return 0;',
            []
        ));
        if ($prototypeAjax > 0) {
            $ready = false;
        }
        else {
            Driver::$previousPrototypeAjax = 0;
            Driver::$prototypeAjaxFailures = 0;
        }

        // require.js module definitions
        $activeDefinitionScript =
            'if (!window.requirejs) {
                return null;
            }
            var contexts = window.requirejs.s.contexts;
            for (var label in contexts) {
                if (contexts.hasOwnProperty(label)) {
                    var registry = contexts[label].registry;
                    for (var module in registry) {
                        if (registry.hasOwnProperty(module) && registry[module].enabled) {
                            return module;
                        }
                    }
                }
            }
            return null;';
        $moduleInProgress = $driver->executeScript($activeDefinitionScript, []);
        if ($moduleInProgress === 'null') {
            $moduleInProgress = null;
        }
        if (!is_null($moduleInProgress)) {
            $ready = false;
        }

        return $ready;
    }

    /**
     * Set focus on element
     *
     * @param ElementInterface $element
     * @return mixed
     */
    public function focus(ElementInterface $element)
    {
        $elementId = $element->getAttribute('id');
        if ($elementId) {
            $js = "if (window.jQuery != undefined) jQuery('#$elementId').focus(); ";
            $js .= "var element = document.getElementById('$elementId'); if (element != undefined) element.focus();";
            $this->driver->executeScript($js, []);
        } else {
            $element->click();
        }
    }

    /**
     * Upload file.
     *
     * @param ElementInterface $element
     * @param string $path
     * @return void
     */
    public function uploadFile(ElementInterface $element, $path)
    {
        $element = $this->getNativeElement($element, false);
        $element->sendKeys($path);
    }

    /**
     * Press a modifier key.
     *
     * @param string $key
     * @param bool $isSpecialKey
     * @return void
     */
    public function pressKey($key, $isSpecialKey = false)
    {
        $this->driver->getKeyboard()->pressKey($key);
    }
}
