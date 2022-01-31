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

namespace Gantry\Component\Stylesheet\Scss;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use ScssPhp\ScssPhp\Compiler;

/**
 * Class Compiler
 * @package Gantry\Component\Stylesheet\Scss
 */
class Functions
{
    protected $compiler;

    /** @var string */
    protected $basePath;
    /** @var array */
    protected $fonts = [];
    /** @var array */
    protected $usedFonts = [];
    /** @var array */
    protected $streamNames = [];
    /** @var array */
    protected $userFunctions = [];

    /**
     * @param Compiler $compiler
     */
    public function setCompiler(Compiler $compiler)
    {
        $this->compiler = $compiler;

        $compiler->registerFunction('url', [$this, 'libUrl'], ['url']);
        $compiler->registerFunction('get-font-url', [$this, 'libGetFontUrl'], ['list...']);
        $compiler->registerFunction('get-font-family', [$this, 'libGetFontFamily'], ['family']);
        $compiler->registerFunction('get-local-fonts', [$this, 'libGetLocalFonts'], ['list...']);
        $compiler->registerFunction('get-local-font-weights', [$this, 'libGetLocalFontWeights'], ['font']);
        $compiler->registerFunction('get-local-font-url', [$this, 'libGetLocalFontUrl'], ['font', 'weight']);

        foreach ($this->userFunctions as $name => $userFunction) {
            $compiler->registerFunction($name, $userFunction[0], $userFunction[1]);
        }
    }

    /**
     * @param string   $name
     * @param callable $func
     * @param array    $prototype
     */
    public function registerFunction($name, $func, $prototype = null)
    {
        $this->userFunctions[$name] = [$func, $prototype];

        if ($this->compiler) {
            $this->compiler->registerFunction($name, $func, $prototype);
        }
    }

    /**
     * @param string $name
     */
    public function unregisterFunction($name)
    {
        unset($this->userFunctions[$name]);

        if ($this->compiler) {
            $this->compiler->unregisterFunction($name);
        }
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        /** @var Document $document */
        $document = Gantry::instance()['document'];

        $this->basePath = rtrim($document::rootUri(), '/') . '/' . Folder::getRelativePath($basePath);
    }

    /**
     * @param array $fonts
     */
    public function setFonts(array $fonts)
    {
        $this->fonts = $fonts;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->usedFonts = [];

        return $this;
    }

    /**
     * @param array $args
     * @return string
     * @throws \ScssPhp\ScssPhp\Exception\CompilerException
     */
    public function libUrl(array $args)
    {
        // Function has a single parameter.
        $parsed = reset($args);
        if (!$parsed) {
            throw $this->compiler->error('url() is missing parameter');
        }
        $url = $this->compiler->compileValue($parsed);
        if (!is_string($url)) {
            throw $this->compiler->error('url() value is not a string');
        }

        // Compile parsed value to string.
        $url = trim($url, '\'"');

        // Handle ../ inside CSS files (points to current theme).
        if (strpos($url, '../') === 0 && strpos($url, '../', 3) === false) {
            $url = 'gantry-theme://' . substr($url, 3);
        }

        /** @var Document $document */
        $document = Gantry::instance()['document'];

        // Generate URL, failed streams will be transformed to 404 URLs.
        $url = $document::url($url, false, null, false);

        // Changes absolute URIs to relative to make the path to work even if the site gets moved.
        if ($url && $url[0] === '/' && $this->basePath) {
            $url = Folder::getRelativePathDotDot($url, $this->basePath);
        }

        // Make sure that all the URLs inside CSS are https compatible by replacing http:// protocol with //.
        if (strpos($url, 'http://') === 0) {
            $url = str_replace('http://', '//', $url);
        }

        // Return valid CSS.
        return "url('{$url}')";
    }

    /**
     * get-font-url($my-font-variable);
     *
     * @param array $args
     * @return string|false
     */
    public function libGetFontUrl($args)
    {
        $value = trim($this->compiler->compileValue(reset($args)), '\'"');

        // It's a google font
        if (0 === strpos($value, 'family=')) {
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
     * @return string
     */
    public function libGetFontFamily($args)
    {
        $value = trim($this->compiler->compileValue(reset($args)), '\'"');

        return $this->encodeFonts($this->decodeFonts($value));
    }

    /**
     * get-local-fonts($my-font-variable, $my-font-variable2, ...);
     *
     * @param array $args
     * @return array
     */
    public function libGetLocalFonts($args)
    {
        $args = $this->compileArgs($args);

        $fonts = [[]];
        foreach ($args as $value) {
            // It's a local font, we need to load any of the mapped fonts from the theme
            $fonts[] = $this->decodeFonts($value, true);
        }
        $fonts = array_merge(...$fonts);
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
     * @return array
     */
    public function libGetLocalFontWeights($args)
    {
        $name = trim($this->compiler->compileValue(reset($args)), '\'"');

        $weights = isset($this->fonts[$name]) ? array_keys($this->fonts[$name]) : [];

        // Create a list of numbers so that SCSS parser can parse the list.
        $list = [];
        foreach ($weights as $weight) {
            $list[] = ['string', '', [(int) $weight]];
        }

        return ['list', ',', $list];
    }

    /**
     * get-local-font-url(roboto, 400);
     *
     * @param array $args
     * @return string|false
     */
    public function libGetLocalFontUrl($args)
    {
        $args = $this->compileArgs($args);

        $name = isset($args[0]) ? trim($args[0], '\'"') : '';
        $weight = isset($args[1]) ? $args[1] : 400;

        // Only return url once per font.
        $weightName = $name . '-' . $weight;
        if (isset($this->fonts[$name][$weight]) && !isset($this->usedFonts[$weightName])) {
            $this->usedFonts[$weightName] = true;

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
        array_walk($fonts, static function(&$val) {
            // Check if font family is one of the 4 default ones, otherwise add quotes.
            if (!\in_array($val, ['cursive', 'serif', 'sans-serif', 'monospace'], true)) {
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
        if (0 === strpos($string, 'family=')) {
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
        array_walk($list, static function(&$val) {
            $val = trim($val, "'\" \t\n\r\0\x0B");
        });
        array_filter($list);

        return $list;
    }

    /**
     * @param array $args
     * @return mixed
     */
    protected function compileArgs($args)
    {
        foreach ($args as &$arg) {
            $arg = $this->compiler->compileValue($arg);
        }

        return $args;
    }
}
