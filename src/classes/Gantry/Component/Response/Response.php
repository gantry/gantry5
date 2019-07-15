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

class Response
{
    public $charset = 'utf-8';
    public $mimeType = 'text/html';

    protected $code = 200;
    protected $message = 'OK';
    protected $lifetime = 0;
    protected $etag;

    /**
     * @var array Response headers.
     */
    protected $headers = [];

    /**
     * @var string Response body.
     */
    protected $content;

    protected $responseCodes = [
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        410 => 'Gone',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        503 => 'Service Temporarily Unavailable'
    ];

    public function __construct($content = '', $status = 200)
    {
        if ($content) {
            $this->setContent($content);
        }

        if ($status != 200) {
            $this->setStatusCode($status);
        }
    }

    /**
     * @param int $seconds
     * @return $this
     */
    public function setLifetime($seconds)
    {
        $this->lifetime = $seconds;

        return $this;
    }

    /**
     * @param mixed $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->etag = md5(json_encode($key));

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * @param int $code
     * @param string $message
     * @return $this
     */
    public function setStatusCode($code, $message = null)
    {
        if ($message) {
            $this->code = $code;
            $this->message = $message;
        } else {
            $this->code = isset($this->responseCodes[$code]) ? (int) $code : 500;
            $this->message = $this->responseCodes[$this->code];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $code = $this->getStatusCode();

        return $code . ' ' . (isset($this->responseCodes[$code]) ? $this->responseCodes[$code] : 'Unknown error');
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     * @param bool $replace
     * @return $this
     */
    public function setHeaders(array $headers, $replace = false)
    {
        foreach ($headers as $key => $values) {
            $act = $replace;
            foreach ((array) $values as $value) {
                $this->setHeader($key, $value, $act);
                $act = false;
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearHeaders()
    {
        $this->headers = [];

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param bool $replace
     * @return $this
     */
    public function setHeader($name, $value, $replace = false)
    {
        if ($replace) {
            $this->headers[$name] = [$value];
        } else {
            $this->headers[$name][] = $value;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return (string) $this->content;
    }

    /**
     * @param string $content
     * @return Response
     * @throws \UnexpectedValueException
     */
    public function setContent($content) {
        if ($content !== null && !is_string($content) && !is_numeric($content) && !is_callable([$content, '__toString'])) {
            throw new \UnexpectedValueException(
                sprintf('Content must be a string or object implementing __toString()')
            );
        }
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->content;
    }
}
