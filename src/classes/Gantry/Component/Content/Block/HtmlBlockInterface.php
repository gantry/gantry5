<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Content\Block;

/**
 * @since 5.4.3
 */
interface HtmlBlockInterface extends ContentBlockInterface
{
    /**
     * @return array
     * @since 5.4.3
     */
    public function getAssets();

    /**
     * @return array
     * @since 5.4.3
     */
    public function getFrameworks();

    /**
     * @param string $location
     * @return array
     * @since 5.4.3
     */
    public function getStyles($location = 'head');

    /**
     * @param string $location
     * @return array
     * @since 5.4.3
     */
    public function getScripts($location = 'head');

    /**
     * @param string $location
     * @return array
     * @since 5.4.3
     */
    public function getHtml($location = 'bottom');

    /**
     * @param string $framework
     * @return $this
     * @since 5.4.3
     */
    public function addFramework($framework);

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     *
     * @example $block->addStyle('assets/js/my.js');
     * @example $block->addStyle(['href' => 'assets/js/my.js', 'media' => 'screen']);
     * @since 5.4.3
     */
    public function addStyle($element, $priority = 0, $location = 'head');

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     * @since 5.4.3
     */
    public function addInlineStyle($element, $priority = 0, $location = 'head');

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     * @since 5.4.3
     */
    public function addScript($element, $priority = 0, $location = 'head');

    /**
     * @param string|array $element
     * @param int $priority
     * @param string $location
     * @return bool
     * @since 5.4.3
     */
    public function addInlineScript($element, $priority = 0, $location = 'head');

    /**
     * @param string $html
     * @param int $priority
     * @param string $location
     * @return bool
     * @since 5.4.3
     */
    public function addHtml($html, $priority = 0, $location = 'bottom');
}
