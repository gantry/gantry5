<?php
namespace Gantry\Framework;

class Exception extends \RuntimeException
{
    protected $responseCodes = [
        200 => '200 OK',
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        410 => '410 Gone',
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        503 => '503 Service Temporarily Unavailable'
    ];

    public function getResponseCode() {
        return isset($this->responseCodes[$this->code]) ? (int) $this->code : 500;
    }

    public function getResponseStatus() {
        return $this->responseCodes[$this->getResponseCode()];
    }
}
