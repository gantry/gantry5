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

namespace Gantry\Component\Theme;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Filesystem\Streams;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Translator\Translator;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class ThemeInstaller
 * @package Gantry\Component\Theme
 */
abstract class ThemeInstaller
{
    /** @var bool Set to true if in Gantry. */
    public $initialized = false;
    /** @var array */
    public $actions = [];

    /** @var string|null */
    protected $name;
    /** @var array */
    protected $outlines;
    /** @var object|null */
    protected $script;

    /**
     * ThemeInstaller constructor.
     * @param string|null $extension
     */
    public function __construct($extension = null)
    {
        if ($extension) {
            $this->name = $extension;
        }
    }

    abstract public function getPath();

    /**
     * Get list of available outlines.
     *
     * @param array $filter
     * @return array
     */
    public function getOutlines(array $filter = null)
    {
        if (!isset($this->outlines)) {
            $this->outlines = [];
            $path = $this->getPath();

            // If no outlines are given, try loading outlines.yaml file.
            $file = YamlFile::instance($path . '/install/outlines.yaml');

            if ($file->exists()) {
                // Load the list from the yaml file.
                $this->outlines = (array) $file->content();
                $file->free();

            } elseif (is_dir($path . '/install/outlines')) {
                // Build the list from the install folder.
                // recurse = false, full=true
                $folders = Folder::all($path . '/install/outlines', ['folders' => true, 'recursive' => false]);
                foreach ($folders as $folder) {
                    $this->outlines[basename($folder)] = [];
                }
            }

            // Always include system outlines.
            $this->outlines += ['default' => [], '_body_only' => [], '_error' => [], '_offline' => []];

        }

        return is_array($filter) ? array_intersect_key($this->outlines, array_flip($filter)) : $this->outlines;
    }

    /**
     * @param string $name
     * @return array
     */
    public function getOutline($name)
    {
        $list = $this->getOutlines([$name]);

        return reset($list);
    }

    public function installDefaults()
    {
        $installerScript = $this->getInstallerScript();

        if ($installerScript && method_exists($installerScript, 'installDefaults')) {
            $installerScript->installDefaults($this);
        } else {
            $this->createDefaults();
        }
    }

    public function installSampleData()
    {
        $installerScript = $this->getInstallerScript();

        if ($installerScript && method_exists($installerScript, 'installSampleData')) {
            $installerScript->installSampleData($this);
        } else {
            $this->createSampleData();
        }
    }

    public function createDefaults()
    {
        $this->createOutlines();
    }

    public function createSampleData()
    {
    }

    /**
     * @param string $template
     * @param array $context
     * @return string
     */
    public function render($template, $context = [])
    {
        try {
            $loader = new FilesystemLoader();
            $loader->setPaths([$this->getPath() . '/install/templates']);

            $params = [
                'cache' => null,
                'debug' => false,
                'autoescape' => 'html'
            ];

            $twig = new Environment($loader, $params);

            $name = $this->name;
            $context += [
                'name' => $this->translate($name),
                'actions' => $this->actions
            ];

            return $twig->render($template, $context);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Set available outlines.
     *
     * @param array $outlines If parameter isn't provided, outlines list get reloaded from the disk.
     * @return $this
     */
    public function setOutlines(array $outlines = null)
    {
        $this->outlines = $outlines;

        return $this;
    }

    /**
     * @param array $filter
     */
    public function createOutlines(array $filter = null)
    {
        $outlines = $this->getOutlines($filter);

        foreach ($outlines as $folder => $params) {
            $this->createOutline($folder, $params);
        }
    }

    /**
     * @param string $folder
     * @param array $params
     * @return string|bool
     */
    public function createOutline($folder, array $params = [])
    {
        if (!$folder) {
            throw new \RuntimeException('Cannot create outline without folder name');
        }

        $this->initialize();

        $params += [
            'preset' => null,
            'title' => null
        ];

        $title = $params['title'] ?: ucwords(trim(strtr($folder, ['_' => ' '])));
        $preset = $params['preset'] ?: 'default';

        // Copy configuration for the new layout.
        if (($this->copyCustom($folder, $folder))) {
            // Update layout and save it.
            $layout = Layout::load($folder, $preset);
            $layout->save()->saveIndex();

            $this->actions[] = ['action' => 'outline_created', 'text' => $this->translate('GANTRY5_INSTALLER_ACTION_OUTLINE_CREATED', $title)];
        }

        return $folder;
    }

    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $name = $this->name;
        $path = $this->getPath();

        // Remove compiled CSS files if they exist.
        $cssPath = $path . '/custom/css-compiled';
        if (is_dir($cssPath)) {
            Folder::delete($cssPath);
        } elseif (is_file($cssPath)) {
            @unlink($cssPath);
        }

        // Remove wrongly named file if it exists.
        $md5path = $path . '/MD5SUM';
        if (is_file($md5path)) {
            @unlink($md5path);
        }

        // Restart Gantry and initialize it.
        $gantry = Gantry::restart();
        $gantry['theme.name'] = $name;

        /** @var Streams $streams */
        $streams = $gantry['streams'];
        $streams->register();

        // Only add error service if debug mode has been enabled.
        if ($gantry->debug()) {
            $gantry->register(new ErrorServiceProvider());
        }

        /** @var Platform $patform */
        $patform = $gantry['platform'];

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        // Initialize theme stream.
        $details = new ThemeDetails($name);
        $locator->addPath('gantry-theme', '', $details->getPaths(), false, true);

        // Initialize theme cache stream and clear theme cache.
        $cachePath = $patform->getCachePath() . '/' . $name;
        if (is_dir($cachePath)) {
            Folder::delete($cachePath);
        }
        Folder::create($cachePath);
        $locator->addPath('gantry-cache', 'theme', [$cachePath], true, true);

        /** @var Config $global */
        $global = $gantry['global'];

        CompiledYamlFile::$defaultCachePath = $locator->findResource('gantry-cache://theme/compiled/yaml', true, true);
        CompiledYamlFile::$defaultCaching = $global->get('compile_yaml', 1);

        $this->initialized = true;
    }

    public function finalize()
    {
        // Copy standard outlines if they haven't been copied already.
        $this->copyCustom('default', 'default');
        $this->copyCustom('_body_only', '_body_only');
        $this->copyCustom('_error', '_error');
        $this->copyCustom('_offline', '_offline');

        $this->initialize();
    }

    /**
     * @param string $layout
     * @param string $id
     * @return bool True if files were copied over.
     */
    protected function copyCustom($layout, $id)
    {
        $path = $this->getPath();

        // Only copy files if the target id doesn't exist.
        $dst = $path . '/custom/config/' . $id;
        if (!$layout || !$id || is_dir($dst)) {
            return false;
        }

        // New location for G5.3.2+
        $src = $path . '/install/outlines/' . $layout;
        if (!is_dir($src)) {
            // Old and deprecated location.
            $src = $path . '/install/layouts/' . $layout;
        }

        try {
            Folder::create($dst);
            if (is_dir($src)) {
                Folder::copy($src, $dst);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Creating configuration for outline '{$layout}' failed: {$e->getMessage()}", 500, $e);
        }

        return true;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function translate($text)
    {
        /** @var Translator $translator */
        $translator = Gantry::instance()['translator'];

        $args = func_get_args();

        return $translator->translate(...$args);
    }

    /**
     * @return object|null
     */
    protected function getInstallerScript()
    {
        if (!$this->script) {
            $className = ucfirst($this->name) . 'InstallerScript';

            if (!class_exists($className)) {

                $path = "{$this->getPath()}/install.php";
                if (is_file($path)) {
                    require_once $path;
                }
            }

            if (class_exists($className)) {
                $this->script = new $className;
            }
        }

        return $this->script;
    }
}
