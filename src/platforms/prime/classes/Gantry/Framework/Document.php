<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2020 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Content\Document\HtmlDocument;

/**
 * Class Document
 * @package Gantry\Framework
 */
class Document extends HtmlDocument
{
    /**
     * @return string
     */
    public static function rootUri()
    {
        return PRIME_URI;
    }
}
