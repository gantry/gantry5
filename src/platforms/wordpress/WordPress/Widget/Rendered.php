<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress\Widget;

class Rendered
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var string
     */
    protected $contents;

    public function __construct($callback, $contents)
    {
        $this->callback = $callback;
        $this->contents = $contents;
    }

    public function display_callback()
    {
        echo $this->contents;
    }
}
