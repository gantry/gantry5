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

namespace Gantry\Component\Stylesheet;

use Gantry\Component\Gantry\GantryTrait;
use Leafo\ScssPhp\Colors;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class CssCompiler implements CssCompilerInterface
{
    use GantryTrait;

    protected $type;

    protected $name;

    protected $debug = false;

    /**
     * @var array
     */
    protected $fonts;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @var string
     */
    protected $target = 'gantry-theme://css-compiled';

    /**
     * @var string
     */
    protected $configuration = 'default';

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array
     */
    protected $files;

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget($target = null)
    {
        if ($target !== null) {
            $this->target = (string) $target;
        }

        return $this;
    }

    /**
     * @param string $configuration
     * @return $this
     */
    public function setConfiguration($configuration = null)
    {
        if ($configuration !== null) {
            $this->configuration = $configuration;
        }

        return $this;
    }

    /**
     * @param array $fonts
     * @return $this
     */
    public function setFonts(array $fonts = null)
    {
        if ($fonts !== null) {
            // Normalize font data.
            $list = [];
            foreach ($fonts as $family => $data) {
                $family = strtolower($family);

                if (is_array($data)) {
                    // font: [400: url1, 500: url2, 700: url3]
                    $list[$family] = $data;
                } else {
                    // font: url
                    $list[$family] = [400 => (string) $data];
                }
            }
            $this->compiler->setFonts($list);
        }

        return $this;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths = null)
    {
        if ($paths !== null) {
            $this->paths = $paths;
        }

        return $this;
    }

    /**
     * @param array $files
     * @return $this
     */
    public function setFiles(array $files = null)
    {
        if ($files !== null) {
            $this->files = $files;
        }

         return $this;
    }


    /**
     * @param string $name
     * @return string
     */
    public function getCssUrl($name)
    {
        $out = $name . ($this->configuration !== 'default' ? '_'. $this->configuration : '');

        return "{$this->target}/{$out}.css";
    }

    /**
     * @return $this
     */
    public function compileAll()
    {
        foreach ($this->files as $file) {
            $this->compileFile($file);
        }

        return $this;
    }


    public function setVariables(array $variables)
    {
        $this->variables = array_filter($variables);

        foreach($this->variables as &$value) {
            // Check variable against colors and units.
            if (preg_match("/(^(#|rgba?|hsla?)|(%|rem|vh|vw|em|px|cm|mm|ch|vmin|vmax|in|pt|pc|ex)$)/i", $value)) {
                continue;
            }

            // Check variable against predefined color names (we use Leafo SCSS Color class to do that).
            if (isset(Colors::$cssColors[strtolower($value)])) {
                continue;
            }

            // All the unknown values need to be quoted.
            $value = "'{$value}'";
        }

        return $this;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function reset()
    {
        $this->compiler->reset();

        return $this;
    }
}
