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

namespace Gantry\Framework\Markdown;

class ParsedownExtra extends \ParsedownExtra
{
    use ParsedownTrait;

    /**
     * ParsedownExtra constructor.
     *
     * @param array $defaults
     * @throws \Exception
     */
    public function __construct(array $defaults = null)
    {
        parent::__construct();

        $this->init($defaults ?: []);
    }
}
