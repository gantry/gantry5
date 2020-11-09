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
use ScssPhp\ScssPhp\Compiler\Environment;
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
     * Get variable
     *
     * @api
     *
     * @param string                                $name
     * @param boolean                               $shouldThrow
     * @param Environment $env
     * @param boolean                               $unreduced
     *
     * @return mixed|null
     */
    public function get($name, $shouldThrow = true, Environment $env = null, $unreduced = false)
    {
        try {
            return parent::get($name, $shouldThrow, $env, $unreduced);
        } catch (\Exception $e) {
            // FIXME: I don't think this is the way to go anymore.
            echo $e->getMessage() . "\n";
            return ['string', '', ['']];
        }
    }

    /**
     * Adds to list of parsed files
     *
     * Overrides original function without `realpath($path)`. Allows user to create new override files.
     *
     * @api
     * @param string $path
     */
    public function addParsedFile($path)
    {
        if ($path && is_file($path)) {
            $this->parsedFiles[$path] = filemtime($path);
        }
    }

    /**
     * Clean parsed files.
     *
     * This method allows us to speed up compiling multiple files with the same includes repeating multiple times.
     */
    public function cleanParsedFiles()
    {
        $this->parsedFiles = [];
    }

    /**
     * Instantiate parser
     *
     * @param string $path
     *
     * @return Parser
     */
    protected function parserFactory($path)
    {
        $parser = new Parser($path, count($this->sourceNames), $this->encoding, $this->cache);

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        // This one is needed for import loop detenction.
        $this->streamNames[] = $path;

        // Resolve URIs to make CSS line comments to use real paths instead of streams.
        $this->sourceNames[] = $locator->isStream($path) ? $locator->findResource($path, false) : $path;
        $this->addParsedFile($path);

        return $parser;
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
            if (!$env->block) {
                continue;
            }

            // We need to use original paths instead of the resolved ones to detect loops (orig file = overridden file).
            $file = $this->streamNames[$env->block->sourceIndex];

            if (realpath($file) === $name) {
                throw $this->error('An @import loop has been found: %s imports %s', $file, basename($file));
            }
        }
    }

    /**
     * Import file
     *
     * @param string      $path
     * @param OutputBlock $out
     */
    protected function importFile($path, OutputBlock $out)
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        // see if tree is cached
        $realPath = $locator->findResource($path);

        if (isset($this->importCache[$realPath])) {
            // We need to add parsed file also when it's found from the cache. This makes compiling multiple files to work.
            $this->addParsedFile($path);

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
