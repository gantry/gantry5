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
use Leafo\ScssPhp\Parser;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;
    protected $fonts;
    protected $usedFonts;
    protected $parsedFiles;

    public function __construct()
    {
        $this->registerFunction('get-font-url', [$this, 'userGetFontUrl']);
        $this->registerFunction('get-font-family', [$this, 'userGetFontFamily']);
        $this->registerFunction('get-local-fonts', [$this, 'userGetLocalFonts']);
        $this->registerFunction('get-local-font-weights', [$this, 'userGetLocalFontWeights']);
        $this->registerFunction('get-local-font-url', [$this, 'userGetLocalFontUrl']);
    }

    public function setBasePath($basePath)
    {
        $this->basePath = '/' . Folder::getRelativePath($basePath);
    }

    public function getParsedFiles()
    {
        // parsedFiles is a private variable in base class, so we need to override function to see it.
        return $this->parsedFiles;
    }

    public function setFonts(array $fonts)
    {
        $this->fonts = $fonts;
    }

    public function compileArgs($args)
    {
        foreach ($args as &$arg) {
            $arg = $this->compileValue($arg);
        }

        return $args;
    }

    /**
     * Get variable
     *
     * @api
     *
     * @param string    $name
     * @param boolean   $shouldThrow
     * @param \stdClass $env
     *
     * @return mixed
     */
    public function get($name, $shouldThrow = true, $env = null)
    {
        try {
            return parent::get($name, $shouldThrow, $env);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            return ['string', '', ['']];
        }
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
        if ($url && $url[0] == '/' && $this->basePath) {
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

        // It's a google font
        if (substr($value, 0, 7) === 'family=') {
            $fonts = $this->decodeFonts($value);
            $font = reset($fonts);

            // Only return url once per font.
            if ($font && !isset($this->usedFonts[$font])) {
                $this->usedFonts[$font] = true;
                return "url('//fonts.googleapis.com/css?{$value}')";
            }
        }

        return false;
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

        return $this->encodeFonts($this->decodeFonts($value));
    }

    /**
     * get-local-fonts($my-font-variable, $my-font-variable2, ...);
     *
     * @param array $args
     * @param Compiler $compiler
     * @return string
     */
    public function userGetLocalFonts($args, Compiler $compiler)
    {
        $args = $this->compileArgs($args);

        $fonts = [];
        foreach ($args as $value) {
            // It's a local font, we need to load any of the mapped fonts from the theme
            $fonts = array_merge($fonts, $this->decodeFonts($value, true));
        }

        $fonts = $this->getLocalFonts($fonts);

        // Create a basic list of strings so that SCSS parser can parse the list.
        $list = [];
        foreach ($fonts as $font => $data) {
            $list[] = ['string', '"', [$font]];
        }

        return ['list', ',', $list];
    }

    /**
     * get-local-font-weights(roboto);
     *
     * @param array $args
     * @param Compiler $compiler
     * @return string
     */
    public function userGetLocalFontWeights($args, Compiler $compiler)
    {
        $name = trim($compiler->compileValue(reset($args)), '\'"');

        $weights = isset($this->fonts[$name]) ? array_keys($this->fonts[$name]) : [];

        // Create a list of numbers so that SCSS parser can parse the list.
        $list = [];
        foreach ($weights as $weight) {
            $list[] = ['number', $weight, ''];
        }

        return ['list', ',', $list];
    }

    /**
     * get-local-font-url(roboto, 400);
     *
     * @param array $args
     * @param Compiler $compiler
     * @return string
     */
    public function userGetLocalFontUrl($args, Compiler $compiler)
    {
        $args = $this->compileArgs($args);

        $name = isset($args[0]) ? trim($args[0], '\'"') : '';
        $weight = isset($args[1]) ? $args[1] : 400;

        // Only return url once per font.
        if (isset($this->fonts[$name][$weight]) && !isset($this->usedFonts[$name . '-' . $weight])) {
            $this->usedFonts[$name . '-' . $weight] = true;

            return $this->fonts[$name][$weight];
        }

        return false;
    }

    /**
     * Get local font data.
     *
     * @param array $fonts
     * @return array
     */
    protected function getLocalFonts(array $fonts)
    {
        $list = [];
        foreach ($fonts as $family) {
            $family = strtolower($family);

            if (isset($this->fonts[$family])) {
                $list[$family] = $this->fonts[$family];
            }
        }

        return $list;
    }

    /**
     * Convert array of fonts into a CSS parameter string.
     *
     * @param array $fonts
     * @return string
     */
    protected function encodeFonts(array $fonts)
    {
        array_walk($fonts, function(&$val) {
            // Check if font family is one of the 4 default ones, otherwise add quotes.
            if (!in_array($val, ['cursive', 'serif', 'sans-serif', 'monospace'])) {
                $val = '"' . $val . '"';
            }
        });

        return implode(', ', $fonts);
    }

    /**
     * Convert string into array of fonts.
     *
     * @param  string $string
     * @param  bool   $localOnly
     * @return array
     */
    protected function decodeFonts($string, $localOnly = false)
    {
        if (substr($string, 0, 7) === 'family=') {
            if ($localOnly) {
                // Do not return external fonts.
                return [];
            }

            // Matches google font family name
            preg_match('/^family=([^&:]+).*$/ui', $string, $matches);
            return [urldecode($matches[1])];
        }

        // Filter list of fonts and quote them.
        $list = (array) explode(',', $string);
        array_walk($list, function(&$val) {
            $val = trim($val, "'\" \t\n\r\0\x0B");
        });
        array_filter($list);

        return $list;
    }

    public function reset()
    {
        $this->usedFonts = [];

        return $this;
    }

    /**
     * Override function to improve the logic.
     *
     * @param $path
     * @param $out
     */
    protected function importFile($path, $out)
    {
        // see if tree is cached
        if (!isset($this->importCache[$path])) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $filename = $locator($path);

            $file = ScssFile::instance($filename);
            $this->importCache[$path] = $file->content();
            $file->free();
        }

        if (!isset($this->parsedFiles[$path])) {
            $gantry = Gantry::instance();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $filename = $locator($path);

            $this->parsedFiles[$path] = filemtime($filename);
        }

        $tree = $this->importCache[$path];

        $dirname = dirname($path);
        array_unshift($this->importPaths, $dirname);
        $this->compileChildren($tree->children, $out);
        array_shift($this->importPaths);
    }
}
