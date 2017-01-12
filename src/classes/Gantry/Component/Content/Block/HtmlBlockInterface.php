<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Content\Block;

/**
 * The Config class contains configuration information.
 *
 * @author RocketTheme
 * @since 5.4.3
 */
interface HtmlBlockInterface extends ContentBlockInterface
{
    public function getAssets();

    public function addFramework($framework);
    public function addStyle($element, $priority = 0, $location = 'head');
    public function addInlineStyle($element, $priority = 0, $location = 'head');
    public function addScript($element, $priority = 0, $location = 'head');
    public function addInlineScript($element, $priority = 0, $location = 'head');
    public function addHtml($html, $priority = 0, $location = 'bottom');
}
