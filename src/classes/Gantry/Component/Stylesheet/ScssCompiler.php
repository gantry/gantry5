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

use Composer\Autoload\ClassLoader;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Stylesheet\Scss\Functions;
use Gantry\Debugger;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Grav\Common\Plugins;
use ScssPhp\ScssPhp\CompilationResult;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\CompilerException;
use RocketTheme\Toolbox\File\File;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use ScssPhp\ScssPhp\Logger\StreamLogger;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\ValueConverter;
use ScssPhp\ScssPhp\Version;

/**
 * Class ScssCompiler
 * @package Gantry\Component\Stylesheet
 */
class ScssCompiler extends CssCompiler
{
    /** @var string */
    public $type = 'scss';
    /** @var string */
    public $name = 'SCSS';

    /** @var CompilationResult|null */
    protected $result;
    /** @var Functions */
    protected $functions;
    protected $compatMode = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!class_exists(Compiler::class, false)) {
            /** @var ClassLoader $loader */
            $loader = static::gantry()['loader'];

            /** @var Theme $theme */
            $theme = static::gantry()['theme'];
            $config = $theme->configuration();
            $version = preg_replace('/[^\d.]+/', '', (string)(isset($config['dependencies']['gantry']) ? $config['dependencies']['gantry'] : '5.0'));
            if (version_compare($version, '5.5', '<')) {
                $this->compatMode = true;
                /** @phpstan-ignore-next-line */
                $loader->setPsr4('ScssPhp\\ScssPhp\\', GANTRY5_LIBRARY . '/compat/vendor/scssphp/scssphp/src');
            } else {
                /** @phpstan-ignore-next-line */
                $loader->setPsr4('ScssPhp\\ScssPhp\\', GANTRY5_LIBRARY . '/vendor/scssphp/scssphp/src');
            }

            // Do not use SCSS compiler from Grav Admin.
            $adminPlugin = class_exists(Plugins::class) ? Plugins::getPlugin('admin') : null;
            if ($adminPlugin && method_exists($adminPlugin, 'getAutoloader')) {
                $adminLoader = $adminPlugin->getAutoloader();
                if ($adminLoader) {
                    $adminLoader->setPsr4('ScssPhp\\ScssPhp\\', '');
                }
            }
        }

        if (\GANTRY_DEBUGGER) {
            Debugger::addMessage('Using SCSS PHP library v' . Version::VERSION);
        }

        parent::__construct();

        $this->functions = new Functions();
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->functions->reset();

        return $this;
    }

    public function resetCache()
    {
    }

    /**
     * @param string $in    Filename without path or extension.
     * @return bool         True if the output file was saved.
     * @throws \RuntimeException
     */
    public function compileFile($in)
    {
        // Buy some extra time as compilation may take a lot of time in shared environments.
        @set_time_limit(30);
        @set_time_limit(60);
        @set_time_limit(90);
        @set_time_limit(120);

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $out = $this->getCssUrl($in);
        /** @var string $path */
        $path = $locator->findResource($out, true, true);
        $file = File::instance($path);

        // Attempt to lock the file for writing.
        try {
            $file->lock(false);
        } catch (\Exception $e) {
            // Another process has locked the file; we will check this in a bit.
        }

        if ($file->locked() === false) {
            // File was already locked by another process, lets avoid compiling the same file twice.
            return false;
        }

        $logfile = fopen('php://memory', 'rb+');
        $logger = new StreamLogger($logfile, true);

        $compiler = $this->getCompiler();
        $compiler->setLogger($logger);

        // Set the lookup paths.
        $this->functions->setBasePath($path);
        $compiler->setImportPaths([[$this, 'findImport']]);

        // Run the compiler.
        $compiler->addVariables($this->getVariables(true));
        $scss = '@import "' . $in . '.scss"';
        try {
            $this->result = $compiler->compileString($scss);
            $css = $this->result->getCss();
        } catch (CompilerException $e) {
            throw new \RuntimeException("CSS Compilation on file '{$in}.scss' failed on error: {$e->getMessage()}", 500, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException("CSS Compilation on file '{$in}.scss' failed on fatal error: {$e->getMessage()}", 500, $e);
        }
        if (strpos($css, $scss) === 0) {
            $css = '/* ' . $scss . ' */';
        }

        // Extract map from css and save it as separate file.
        $pos = strrpos($css, '/*# sourceMappingURL=');
        if ($pos !== false) {
            $map = json_decode(urldecode(substr($css, $pos + 43, -3)), true);

            /** @var Document $document */
            $document = $gantry['document'];

            foreach ($map['sources'] as &$source) {
                $source = $document::url($source, false, -1);
            }
            unset($source);

            $mapFile = JsonFile::instance($path . '.map');
            $mapFile->save($map);
            $mapFile->free();

            $css = substr($css, 0, $pos) . '/*# sourceMappingURL=' . basename($out) . '.map */';
        }

        $warnings = preg_replace('/\n +(\w)/mu', '\1', stream_get_contents($logfile, -1, 0));
        if ($warnings) {
            $warnings = explode("\n\n", $warnings);
            foreach ($warnings as $i => $warning) {
                if ($warning === '') {
                    unset($warnings[$i]);
                    continue;
                }
                if (strpos($warning, '[Bourbon] [Deprecation]') !== false) {
                    if (\GANTRY_DEBUGGER) {
                        Debugger::addMessage("{$in}: {$warning}", 'deprecated');
                    }
                    if ($this->compatMode) {
                        unset($warnings[$i]);
                    }
                } else {
                    if (\GANTRY_DEBUGGER) {
                        Debugger::addMessage("{$in}: {$warning}", 'warning');
                    }
                }
            }

            if ($warnings) {
                $this->warnings[$in] = array_values($warnings);
            }
        }

        if (!$this->production) {
            $warning = <<<WARN
/* GANTRY5 DEVELOPMENT MODE ENABLED.
 *
 * WARNING: This file is automatically generated by Gantry5. Any modifications to this file will be lost!
 *
 * For more information on modifying CSS, please read:
 *
 * http://docs.gantry.org/gantry5/configure/styles
 * http://docs.gantry.org/gantry5/tutorials/adding-a-custom-style-sheet
 */
WARN;
            $css = $warning . "\n\n" . $css;
        } else {
            $css = "{$this->checksum()}\n{$css}";
        }

        $file->save($css);
        $file->unlock();
        $file->free();

        $this->createMeta($out, md5($css));

        $this->reset();

        return true;
    }

    /**
     * @param string   $name       Name of function to register to the compiler.
     * @param callable $callback   Function to run when called by the compiler.
     * @return $this
     */
    public function registerFunction($name, callable $callback)
    {
        $this->functions->registerFunction($name, $callback);

        return $this;
    }

    /**
     * @param string $name       Name of function to unregister.
     * @return $this
     */
    public function unregisterFunction($name)
    {
        $this->functions->unregisterFunction($name);

        return $this;
    }

    /**
     * @param string $url
     * @return null|string
     * @internal
     */
    public function findImport($url)
    {
        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Ignore vanilla css and external requests.
        if (preg_match('/\.css$|^https?:\/\//', $url)) {
            return null;
        }

        // Try both normal and the _partial filename.
        $files = [$url, preg_replace('/[^\/]+$/', '_\0', $url)];

        foreach ($this->paths as $base) {
            foreach ($files as $file) {
                if (!preg_match('|\.scss$|', $file)) {
                    $file .= '.scss';
                }
                $filepath = $locator->findResource($base . '/' . $file);
                if ($filepath) {
                    return $filepath;
                }
            }
        }

        return null;
    }

    /**
     * @param bool $encoded
     * @return array
     */
    public function getVariables($encoded = false)
    {
        $variables = $this->variables;
        if (!$encoded) {
            return $variables;
        }

        $list = [];
        foreach($variables as $key => $value) {
            $list[$key] = ValueConverter::parseValue($value);
        }

        return $list;
    }

    /**
     * @return Compiler
     */
    protected function getCompiler()
    {
        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];
        $cacheDir = $locator->findResource('gantry-cache://theme/scss/source', true, true);
        if (!file_exists($cacheDir)) {
            Folder::create($cacheDir);
        }

        $compiler = new Compiler(['cacheDir' => $cacheDir]);

        $this->functions->setCompiler($compiler);

        if ($this->production) {
            $compiler->setOutputStyle(OutputStyle::COMPRESSED);
        } else {
            $compiler->setOutputStyle(OutputStyle::EXPANDED);
            $compiler->setSourceMap(Compiler::SOURCE_MAP_INLINE);
            // TODO: Look if we can / should use option to let compiler to save the source map.
            $compiler->setSourceMapOptions([
                'sourceMapRootpath' => '',
                'sourceMapBasepath' => GANTRY5_ROOT,
            ]);
        }

        return $compiler;
    }

    /**
     * @param array $list
     */
    protected function doSetFonts(array $list)
    {
        $this->functions->setFonts($list);
    }

    /**
     * @return array
     */
    protected function getIncludedFiles()
    {
        $list = [];
        foreach ($this->result->getIncludedFiles() as $filename) {
            $list[$filename] = filemtime($filename);
        }

        return $list;
    }
}
