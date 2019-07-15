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

namespace Gantry\Component\Theme;

use Gantry\Component\Config\Config;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Stylesheet\CssCompilerInterface;

/**
 * Class ThemeTrait
 * @package Gantry\Framework\Base
 *
 * @property string $path
 * @property string $layout
 */
interface ThemeInterface
{
    // AbstractTheme class

    /**
     * Get context for render().
     *
     * @param array $context
     * @return array
     */
    public function getContext(array $context);

    /**
     * Define twig environment.
     *
     * @param \Twig_Environment $twig
     * @param \Twig_LoaderInterface $loader
     * @return \Twig_Environment
     */
    public function extendTwig(\Twig_Environment $twig, \Twig_LoaderInterface $loader = null);

    /**
     * Returns renderer.
     *
     * @return \Twig_Environment
     */
    public function renderer();

    /**
     * Render a template file.
     *
     * @param string $file
     * @param array $context
     * @return string
     */
    public function render($file, array $context = array());

    // ThemeTrait class

    /**
     * Update all CSS files in the theme.
     *
     * @param array $outlines
     * @return array List of CSS warnings.
     */
    public function updateCss(array $outlines = null);

    /**
     * Set current layout.
     *
     * @param string $name
     * @param bool $force
     * @return $this
     */
    public function setLayout($name = null, $force = false);

    /**
     * Get current preset.
     *
     * @param  bool $forced     If true, return only forced preset or null.
     * @return string|null $preset
     */
    public function preset($forced = false);

    /**
     * Set preset to be used.
     *
     * @param string $name
     * @return $this
     */
    public function setPreset($name = null);

    /**
     * Return CSS compiler used in the theme.
     *
     * @return CssCompilerInterface
     * @throws \RuntimeException
     */
    public function compiler();

    /**
     * Returns URL to CSS file.
     *
     * If file does not exist, it will be created by using CSS compiler.
     *
     * @param string $name
     * @return string
     */
    public function css($name);

    /**
     * Return all CSS variables.
     *
     * @return array
     */
    public function getCssVariables();

    /**
     * Returns style presets for the theme.
     *
     * @return Config
     */
    public function presets();

    /**
     * Return name of the used layout preset.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function type();

    /**
     * Load current layout and its configuration.
     *
     * @param string $name
     * @return Layout
     * @throws \LogicException
     */
    public function loadLayout($name = null);

    /**
     * Check whether layout has content bock.
     *
     * @return bool
     */
    public function hasContent();

    /**
     * Returns all non-empty segments from the layout.
     *
     * @return array
     */
    public function segments();

    /**
     * Returns details of the theme.
     *
     * @return ThemeDetails
     */
    public function details();

    /**
     * Returns configuration of the theme.
     *
     * @return array
     */
    public function configuration();

    /**
     * Function to convert block sizes into CSS classes.
     *
     * @param $text
     * @return string
     */
    public function toGrid($text);
}
