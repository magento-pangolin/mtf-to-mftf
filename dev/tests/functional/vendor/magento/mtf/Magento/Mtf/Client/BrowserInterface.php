<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Client;

/**
 * Interface Browser
 *
 * Interface provides declaration of methods to perform browser actions such as navigation,
 * working with windows, alerts, prompts etc.
 *
 * @api
 */
interface BrowserInterface
{
    /**
     * Open page
     *
     * @param string $url
     * @return void
     */
    public function open($url);

    /**
     * Back to previous page
     * @return void
     */
    public function back();

    /**
     * Forward page
     *
     * @return void
     */
    public function forward();

    /**
     * Refresh page
     *
     * @return void
     */
    public function refresh();

    /**
     * Reopen browser
     *
     * @return void
     */
    public function reopen();

    /**
     * Change the focus to a frame in the page by locator
     *
     * @param Locator|null $locator
     * @return void
     */
    public function switchToFrame(Locator $locator = null);

    /**
     * Open new tab/window in Browser and switch to it.
     *
     * @return void
     */
    public function openNewWindow();

    /**
     * Close the current window or specified one.
     *
     * @param string|null $handle [optional]
     * @return void
     */
    public function closeWindow($handle = null);

    /**
     * Changes the focus to the specified window or to the latest one.
     *
     * @param string|null $handle [optional]
     * @return void
     */
    public function selectWindow($handle = null);

    /**
     * Retrieves the current window handle.
     *
     * @return string
     */
    public function getCurrentWindow();

    /**
     * Retrieves a list of all available window handles.
     *
     * @return array
     */
    public function getWindowHandles();

    /**
     * Find element on the page
     *
     * @param string $selector
     * @param string $strategy
     * @param string $type = select|multiselect|checkbox|null OR custom class with full namespace
     * @param ElementInterface $context
     * @return ElementInterface
     */
    public function find(
        $selector,
        $strategy = Locator::SELECTOR_CSS,
        $type = null,
        ElementInterface $context = null
    );

    /**
     * Wait until callback isn't null or timeout occurs
     *
     * @param callback $callback
     * @return mixed
     */
    public function waitUntil($callback);

    /**
     * Press OK on an alert, or confirms a dialog
     *
     * @return void
     */
    public function acceptAlert();

    /**
     * Press Cancel on an alert, or does not confirm a dialog
     *
     * @return void
     */
    public function dismissAlert();

    /**
     * Get the alert dialog text
     *
     * @return string
     */
    public function getAlertText();

    /**
     * Set the text to a prompt popup
     *
     * @param string $text
     * @return void
     */
    public function setAlertText($text);

    /**
     * Get current page url
     *
     * @return string
     */
    public function getUrl();

    /**
     * Get Html page source
     *
     * @return string
     */
    public function getHtmlSource();

    /**
     * Get binary string of image
     *
     * @return string
     */
    public function getScreenshotData();

    /**
     * Inject Js Error collector
     *
     * @return void
     */
    public function injectJsErrorCollector();

    /**
     * Get js errors
     *
     * @return string[]
     */
    public function getJsErrors();

    /**
     * Get page title text.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Press a modifier key.
     *
     * @param $key
     * @param bool $isSpecialKey
     * @return void
     */
    public function pressKey($key, $isSpecialKey = false);
}
