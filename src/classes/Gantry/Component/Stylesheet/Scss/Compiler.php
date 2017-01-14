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

namespace Gantry\Component\Stylesheet\Scss;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Leafo\ScssPhp\Compiler as BaseCompiler;
use Leafo\ScssPhp\Parser;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Compiler extends BaseCompiler
{
    protected $basePath;
    protected $fonts;
    protected $usedFonts;
    protected $streamNames;
    protected $parsedFiles = [];

    public function __construct()
    {
        parent::__construct();

        $this->registerFunction('get-font-url', [$this, 'userGetFontUrl']);
        $this->registerFunction('get-font-family', [$this, 'userGetFontFamily']);
        $this->registerFunction('get-local-fonts', [$this, 'userGetLocalFonts']);
        $this->registerFunction('get-local-font-weights', [$this, 'userGetLocalFontWeights']);
        $this->registerFunction('get-local-font-url', [$this, 'userGetLocalFontUrl']);
    }

    public function setBasePath($basePath)
    {
        /** @var Document $document */
        $document = Gantry::instance()['document'];
        $this->basePath = rtrim($document->rootUri(), '/') . '/' . Folder::getRelativePath($basePath);
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
     * @param BaseCompiler\Environment $env
     *
     * @return mixed
     */
    public function get($name, $shouldThrow = true, BaseCompiler\Environment $env = null)
    {
        try {
            return parent::get($name, $shouldThrow, $env);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            return ['string', '', ['']];
        }
    }

    /**
     * @param array $args
     * @return string
     * @throws \Leafo\ScssPhp\Exception\CompilerException
     */
    public function libUrl(array $args)
    {
        // Function has a single parameter.
        $parsed = reset($args);
        if (!$parsed) {
            $this->throwError('url() is missing parameter');
        }

        // Compile parsed value to string.
        $url = trim($this->compileValue($parsed), '\'"');

        // Handle ../ inside CSS files (points to current theme).
        if (strpos($url, '../') === 0 && strpos($url, '../', 3) === false) {
            $url = 'gantry-theme://' . substr($url, 3);
        }

        // Generate URL, failed streams will be transformed to 404 URLs.
        $url = Gantry::instance()['document']->url($url, false, null, false);

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
     * @return string
     */
    public function userGetFontUrl($args)
    {
        $value = trim($this->compileValue(reset($args)), '\'"');

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
     * @return string
     */
    public function userGetFontFamily($args)
    {
        $value = trim($this->compileValue(reset($args)), '\'"');

        return $this->encodeFonts($this->decodeFonts($value));
    }

    /**
     * get-local-fonts($my-font-variable, $my-font-variable2, ...);
     *
     * @param array $args
     * @return string
     */
    public function userGetLocalFonts($args)
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
     * @return string
     */
    public function userGetLocalFontWeights($args)
    {
        $name = trim($this->compileValue(reset($args)), '\'"');

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
     * @return string
     */
    public function userGetLocalFontUrl($args)
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
     * Instantiate parser
     *
     * @param string $path
     *
     * @return \Leafo\ScssPhp\Parser
     */
    protected function parserFactory($path)
    {
        $parser = new Parser($path, count($this->sourceNames), $this->encoding);

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        $this->sourceNames[] = $locator->isStream($path) ? $locator->findResource($path, false) : $path;
        $this->streamNames[] = $path;
        $this->addParsedFile($path);
        return $parser;
    }

    /**
     * Adds to list of parsed files
     *
     * @api
     *
     * @param string $path
     */
    public function addParsedFile($path)
    {
        if ($path && file_exists($path)) {
            $this->parsedFiles[$path] = filemtime($path);
        }
    }

    /**
     * Returns list of parsed files
     *
     * @api
     *
     * @return array
     */
    public function getParsedFiles()
    {
        return $this->parsedFiles;
    }

    /**
     * Clean parset files.
     *
     * @api
     */
    public function cleanParsedFiles()
    {
        $this->parsedFiles = [];
    }

    /**
     * Handle import loop
     *
     * @param string $name
     *
     * @throws \Exception
     */
    protected function handleImportLoop($name)
    {
        for ($env = $this->env; $env; $env = $env->parent) {
            $file = $this->streamNames[$env->block->sourceIndex];

            if (realpath($file) === $name) {
                $this->throwError('An @import loop has been found: %s imports %s', $file, basename($file));
                break;
            }
        }
    }

    /**
     * Override function to improve the logic.
     *
     * @param string $path
     * @param mixed  $out
     */
    protected function importFile($path, $out)
    {
        $this->addParsedFile($path);

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        // see if tree is cached
        $realPath = $locator($path);

        if (isset($this->importCache[$realPath])) {
            $this->handleImportLoop($realPath);

            $tree = $this->importCache[$realPath];
        } else {
            $code   = file_get_contents($realPath);
            $parser = $this->parserFactory($path);
            $tree   = $parser->parse($code);

            $this->importCache[$realPath] = $tree;
        }

        $dirname = dirname($path);
        array_unshift($this->importPaths, $dirname);
        $this->compileChildrenNoReturn($tree->children, $out);
        array_shift($this->importPaths);
    }
}
