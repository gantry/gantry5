<?php
namespace Gantry\Component\Request;

use Gantry\Framework\Base\Gantry;

class Request
{
    protected $method;

    public function getMethod()
    {
        if (!$this->method) {
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
            if ('POST' === $method) {
                $method = isset($_SERVER['X-HTTP-METHOD-OVERRIDE']) ? $_SERVER['X-HTTP-METHOD-OVERRIDE'] : $method;
            }
            $this->method = strtoupper($method);
        }

        return $this->method;
    }
}
