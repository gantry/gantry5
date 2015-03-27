<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework\Base;

use Gantry\Component\Theme\ThemeDetails;
use RocketTheme\Toolbox\File\JsonFile;

abstract class Theme
{
    use ThemeTrait;

    public $name;
    public $url;
    public $path;
    public $layout;

    /**
     * @var ThemeDetails
     */
    protected $details;

    public function __construct($path, $name = '')
    {
        if (!is_dir($path)) {
            throw new \LogicException('Theme not found!');
        }

        $this->path = $path;
        $this->name = $name ? $name : basename($path);
        $this->init();
    }

    abstract public function render($file, array $context = array());
}
