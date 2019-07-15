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
