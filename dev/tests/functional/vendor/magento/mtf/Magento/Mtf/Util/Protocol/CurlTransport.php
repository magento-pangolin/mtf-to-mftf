<?php
/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Mtf\Util\Protocol;

/**
 * HTTP CURL Adapter.
 */
class CurlTransport implements CurlInterface
{
    /**
     * Parameters array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Curl handle.
     *
     * @var resource
     */
    protected $resource;

    /**
     * Allow parameters.
     *
     * @var array
     */
    protected $allowedParams = [
        'timeout' => CURLOPT_TIMEOUT,
        'maxredirects' => CURLOPT_MAXREDIRS,
        'proxy' => CURLOPT_PROXY,
        'ssl_cert' => CURLOPT_SSLCERT,
        'userpwd' => CURLOPT_USERPWD,
    ];

    /**
     * Array of CURL options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Apply current configuration array to curl resource.
     *
     * @return $this
     */
    protected function applyConfig()
    {
        // apply additional options to cURL
        foreach ($this->options as $option => $value) {
            curl_setopt($this->getResource(), $option, $value);
        }

        if (empty($this->config)) {
            return $this;
        }
        foreach (array_keys($this->config) as $param) {
            if (array_key_exists($param, $this->allowedParams)) {
                curl_setopt($this->getResource(), $this->allowedParams[$param], $this->config[$param]);
            }
        }
        return $this;
    }

    /**
     * Set array of additional cURL options.
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options = [])
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Add additional option to cURL.
     *
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function addOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Set the configuration array for the adapter.
     *
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config = [])
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Send request to the remote server.
     *
     * @param string $url
     * @param mixed $params
     * @param string $method
     * @param mixed $headers
     * @return void
     * @throws \Exception
     */
    public function write($url, $params = [], $method = CurlInterface::POST, $headers = [])
    {
        $this->applyConfig();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEFILE => '',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
        ];
        switch ($method) {
            case CurlInterface::POST:
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $params;
                break;
            case CurlInterface::PUT:
                $options[CURLOPT_CUSTOMREQUEST] = self::PUT;
                $options[CURLOPT_POSTFIELDS] = $params;
                break;
            case CurlInterface::DELETE:
                $options[CURLOPT_CUSTOMREQUEST] = self::DELETE;
                break;
            case CurlInterface::GET:
                $options[CURLOPT_HTTPGET] = true;
                break;
            default:
                throw new \Exception("Undefined curl method: $method");
        }

        curl_setopt_array($this->getResource(), $options);
    }

    /**
     * Read response from server.
     *
     * @return string
     * @throws \Exception
     */
    public function read()
    {
        $response = curl_exec($this->getResource());
        if ($response === false) {
            throw new \Exception(curl_error($this->getResource()));
        }
        return $response;
    }

    /**
     * Close the connection to the server.
     *
     * @return void
     */
    public function close()
    {
        curl_close($this->getResource());
        $this->resource = null;
    }

    /**
     * Returns a cURL handle on success.
     *
     * @return resource
     */
    protected function getResource()
    {
        if ($this->resource === null) {
            $this->resource = curl_init();
        }
        return $this->resource;
    }

    /**
     * Get last error number.
     *
     * @return int
     */
    public function getErrno()
    {
        return curl_errno($this->getResource());
    }

    /**
     * Get string with last error for the current session.
     *
     * @return string
     */
    public function getError()
    {
        return curl_error($this->getResource());
    }

    /**
     * Get information regarding a specific transfer.
     *
     * @param int $opt CURLINFO option
     * @return mixed
     */
    public function getInfo($opt = 0)
    {
        return curl_getinfo($this->getResource(), $opt);
    }

    /**
     * curl_multi_* requests support.
     *
     * @param array $urls
     * @param array $options
     * @return array
     */
    public function multiRequest(array $urls, array $options = [])
    {
        $handles = [];
        $result = [];

        $multiHandle = curl_multi_init();

        foreach ($urls as $key => $url) {
            $handles[$key] = curl_init();
            curl_setopt($handles[$key], CURLOPT_URL, $url);
            curl_setopt($handles[$key], CURLOPT_HEADER, 0);
            curl_setopt($handles[$key], CURLOPT_RETURNTRANSFER, 1);
            if (!empty($options)) {
                curl_setopt_array($handles[$key], $options);
            }
            curl_multi_add_handle($multiHandle, $handles[$key]);
        }
        $process = null;
        do {
            curl_multi_exec($multiHandle, $process);
            usleep(100);
        } while ($process > 0);

        foreach ($handles as $key => $handle) {
            $result[$key] = curl_multi_getcontent($handle);
            curl_multi_remove_handle($multiHandle, $handle);
        }
        curl_multi_close($multiHandle);
        return $result;
    }

    /**
     * Extract the response code from a response string.
     *
     * @param string $responseStr
     * @return int
     */
    public static function extractCode($responseStr)
    {
        preg_match("|^HTTP/[\d\.x]+ (\d+)|", $responseStr, $m);

        if (isset($m[1])) {
            return (int)$m[1];
        } else {
            return false;
        }
    }
}
