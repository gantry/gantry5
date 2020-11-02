<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2020 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Stylesheet\Scss;

use Gantry\Framework\Gantry;
use ScssPhp\ScssPhp\Compiler as BaseCompiler;
use ScssPhp\ScssPhp\Formatter\OutputBlock;
use ScssPhp\ScssPhp\Parser;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Compiler
 * @package Gantry\Component\Stylesheet\Scss
 */
class Compiler extends BaseCompiler
{
    /** @var array */
    protected $streamNames = [];

    /**
     * @param array $args
     * @return mixed
     */
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
     * @param bool $unreduced
     *
     * @return mixed
     */
    public function get($name, $shouldThrow = true, BaseCompiler\Environment $env = null, $unreduced = false)
    {
        try {
            return parent::get($name, $shouldThrow, $env, $unreduced);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            return ['string', '', ['']];
        }
    }

    /**
     * Instantiate parser
     *
     * @param string $path
     *
     * @return \ScssPhp\ScssPhp\Parser
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
     * Clean parsed files.
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
     * @param OutputBlock  $out
     *
     * @throws \Exception
     */
    protected function importFile($path, OutputBlock $out)
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
