<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Stylesheet;

/**
 * Interface CssCompilerInterface
 * @package Gantry\Component\Stylesheet
 */
interface CssCompilerInterface
{
    /**
     * @return array
     */
    public function getWarnings();

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget($target = null);

    /**
     * @param string $configuration
     * @return $this
     */
    public function setConfiguration($configuration = null);

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths = null);

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles(array $files = null);

    /**
     * @param array $fonts
     * @return $this
     */
    public function setFonts(array $fonts);

    /**
     * @param string $name
     * @return string
     */
    public function getCssUrl($name);

    /**
     * @return array
     */
    public function getVariables();

    /**
     * @param array $variables
     * @return $this
     */
    public function setVariables(array $variables);

    /**
     * @param string   $name       Name of function to register to the compiler.
     * @param callable $callback   Function to run when called by the compiler.
     * @return $this
     */
    public function registerFunction($name, callable $callback);

    /**
     * @param string $name       Name of function to unregister.
     * @return $this
     */
    public function unregisterFunction($name);

    /**
     * @param string $in
     * @param callable $variables
     * @return bool
     */
    public function needsCompile($in, $variables);

    /**
     * @param string $in    Filename without path or extension.
     * @return bool         True if the output file was saved.
     * @throws \RuntimeException
     */
    public function compileFile($in);

    /**
     * @return $this
     */
    public function reset();

    /**
     * @return $this
     */
    public function compileAll();

    public function resetCache();
}
