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

namespace Gantry\Component\Stylesheet\Scss;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Document;
use Gantry\Framework\Gantry;
use Leafo\ScssPhp\Compiler as BaseCompiler;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;

    public function __construct()
    {
        $this->registerFunction('get-font-url', [$this, 'userGetFontUrl']);
        $this->registerFunction('get-font-family', [$this, 'userGetFontFamily']);
    }

    public function setBasePath($basePath)
    {
        $this->basePath = '/' . Folder::getRelativePath($basePath);
    }

    public function compileValue($value)
    {
        // Makes protected function public.
        return parent::compileValue($value);
    }

    public function libUrl(array $args, Compiler $compiler)
    {
        // Function has a single parameter.
        $parsed = reset($args);
        if (!$parsed) {
            $this->throwError('url() is missing parameter');
        }

        // Compile parsed value to string.
        $url = trim($compiler->compileValue($parsed), '\'"');

        // Handle ../ inside CSS files (points to current theme).
        $uri = strpos($url, '../') === 0 ? 'gantry-theme://' . substr($url, 3) : $url;

        // Generate URL, failed streams will be kept as they are to allow users to find issues.
        $url = Document::url($uri) ?: $url;

        // Changes absolute URIs to relative to make the path to work even if the site gets moved.
        if ($url[0] == '/' && $this->basePath) {
            $url = Folder::getRelativePathDotDot($url, $this->basePath);
        }

        // Return valid CSS.
        return "url('{$url}')";
    }

    /**
     * get-font-url($my-font-variable);
     *
     * @param array $args
     * @param Compiler $compiler
     * @return string
     */
    public function userGetFontUrl($args, Compiler $compiler)
    {
        $value = trim($compiler->compileValue(reset($args)), '\'"');

        if (substr($value, 0, 7) === 'family=') {
            return "url('http://fonts.googleapis.com/css?{$value}')";
        }

        return '';
    }

    /**
     * font-family: get-font-family($my-font-variable);
     *
     * @param array $args
     * @param Compiler $compiler
     * @return string
     */
    public function userGetFontFamily($args, Compiler $compiler)
    {
        $value = trim($compiler->compileValue(reset($args)), '\'"');

        if (substr($value, 0, 7) === 'family=') {
            // Google font.
            preg_match('/family=(.*?)&/', $value, $matches);
            return '"' . $matches[1] . '"';
        }

        // Filter list of fonts and quote them.
        $list = explode(',', $value);
        array_walk($list, function(&$val) {
            $val = trim($val, "'\" \t\n\r\0\x0B");

            // Check if font family is one of the 4 default ones, otherwise add quotes.
            if (!in_array($val, ['cursive', 'serif', 'sans-serif', 'monospace'])) {
                $val = '"' . $val . '"';
            }
        });
        array_filter($list);

        return implode(', ', $list);
    }
}
