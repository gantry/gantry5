<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Response;

class JsonResponse extends Response
{
    public $mimeType = 'application/json';

    protected $success = true;
    protected $message;
    protected $exceptions = [];
    protected $messages = [];
    protected $content = [];

    /**
     * @param string $content
     * @param bool $success
     * @return $this
     */
    public function setContent($content, $success = true)
    {
        $this->success = (bool) $success;

        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $this->parseValue($key, $value);
            }
        } else {
            $this->parseValue(null, $content);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        // Empty output buffer to make sure that the response is clean and valid.
        while (($output = ob_get_clean()) !== false) {
            // In debug mode send also output buffers (debug dumps, PHP notices and warnings).
            if ($output && (GANTRY5_DEBUG || headers_sent())) {
                $this->messages['php'][] = $output;
            }
        }

        $json = [
            'code' => $this->code,
            'success' => $this->success
        ];
        if ($this->messages) {
            $json['messages'] = $this->messages;
        }
        if ($this->exceptions) {
            $json['exceptions'] = $this->exceptions;
        }
        if (GANTRY5_DEBUG) {
            $json['memory'] = ['peak' => memory_get_peak_usage(), 'current' => memory_get_usage()];
        }
        $json += $this->content;

        return (string) json_encode($json);
    }

    protected function parseValue($key, $value)
    {
        if ($value instanceof \Exception) {
            // Prepare the error response if we are dealing with an error.
            $this->success = false;
            $this->exceptions = $this->parseException($value);

        } elseif ($value instanceof HtmlResponse) {
            // Add HTML response (numeric keys are always integers).
            $key =  !$key || is_int($key) ? 'html' : $key;
            $this->content[$key] = trim((string) $value);

        } elseif (is_null($key)) {
            // If the returned value was not an array, put the contents into data variable.
            $this->content['data'] = $value;

        } elseif (is_int($key)) {
            // If the key was an integer, also put the contents into data variable.
            $this->content['data'][$key] = $value;

        } else {
            $this->content[$key] = $value;
        }
    }

    protected function parseException(\Exception $e)
    {
        $this->code = $e->getCode();

        // Build data from exceptions.
        $exceptions = [];

        do {
            $exception = [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ];

            if (GANTRY5_DEBUG) {
                $exception += [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }

            $exceptions[] = $exception;
            $e = $e->getPrevious();
        }
        while (GANTRY5_DEBUG && $e);

        return $exceptions;
    }
}
