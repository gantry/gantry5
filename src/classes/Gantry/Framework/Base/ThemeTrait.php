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

namespace Gantry\Framework\Base;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Stylesheet\CssCompilerInterface;
use Gantry\Component\Theme\ThemeDetails;
use Gantry\Component\Twig\TwigExtension;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Framework\Services\ErrorServiceProvider;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class ThemeTrait
 * @package Gantry\Framework\Base
 *
 * @property string $path
 * @property string $layout
 */
trait ThemeTrait
{
    use GantryTrait;

    protected $segments;
    protected $preset;

    /**
     * Initialize theme.
     */
    public function init()
    {
        $gantry = static::gantry();
        $gantry['streams'];
        $gantry->register(new ErrorServiceProvider);
    }

    /**
     * Update all CSS files in the theme.
     */
    public function updateCss()
    {
        $gantry = static::gantry();
        $compiler = $this->compiler();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $path = $locator->findResource($compiler->getTarget(), true, true);

        // Make sure that all the CSS files get deleted.
        if (is_dir($path)) {
            Folder::delete($path, false);
        }

        /** @var Configurations $configurations */
        $configurations = $gantry['configurations'];
        foreach ($configurations as $configuration => $title) {
            $config = ConfigServiceProvider::load($gantry, $configuration);

            $compiler->reset()->setConfiguration($configuration)->setVariables($config->flatten('styles', '-'));
            $compiler->compileAll();
        }
    }

    /**
     * Set current layout.
     *
     * @param string $name
     * @return $this
     */
    public function setLayout($name = null)
    {
        $gantry = static::gantry();

        // Set default name only if configuration has not been set before.
        if ($name === null && !isset($gantry['configuration'])) {
            $name = 'default';
        }

        // Set configuration if given.
        if ($name) {
            $gantry['configuration'] = $name;
        }

        return $this;
    }

    /**
     * Set preset to be used.
     *
     * @param string $name
     * @return $this
     */
    public function setPreset($name = null)
    {
        // Set preset if given.
        if ($name) {
            $this->preset = $name;
        }

        return $this;
    }

    /**
     * Return CSS compiler used in the theme.
     *
     * @return CssCompilerInterface
     * @throws \RuntimeException
     */
    public function compiler()
    {
        static $compiler;

        if (!$compiler) {
            $compilerClass = (string) $this->details()->get('configuration.css.compiler', '\Gantry\Component\Stylesheet\ScssCompiler');

            if (!class_exists($compilerClass)) {
                throw new \RuntimeException('CSS compiler used by the theme not found');
            }

            $details = $this->details();

            /** @var CssCompilerInterface $compiler */
            $compiler = new $compilerClass();
            $compiler
                ->setTarget($details->get('configuration.css.target'))
                ->setPaths($details->get('configuration.css.paths'))
                ->setFiles($details->get('configuration.css.files'))
                ->setFonts($details->get('configuration.fonts'));
        }

        if ($this->preset) {
            $compiler->setConfiguration($this->preset);
        } else {
            $gantry = static::gantry();
            $compiler->setConfiguration(isset($gantry['configuration']) ? $gantry['configuration'] : 'default');
        }

        return $compiler->reset();
    }

    /**
     * Returns URL to CSS file.
     *
     * If file does not exist, it will be created by using CSS compiler.
     *
     * @param string $name
     * @return string
     */
    public function css($name)
    {
        $gantry = self::gantry();

        $compiler = $this->compiler();

        $url = $compiler->getCssUrl($name);

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $path = $locator->findResource($url, true, true);

        if (!is_file($path)) {
            if ($this->preset) {
                $variables = $this->presets()->flatten($this->preset . '.styles', '-');
            } else {
                $variables = $gantry['config']->flatten('styles', '-');
            }
            $compiler->setVariables($variables);
            $compiler->compileFile($name);
        }

        return $url;
    }

    /**
     * Returns style presets for the theme.
     *
     * @return Config
     */
    public function presets()
    {
        static $presets;

        if (!$presets) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $filename = $locator->findResource("gantry-theme://gantry/presets.yaml");

            $presets = new Config(CompiledYamlFile::instance($filename)->content());
        }

        return $presets;
    }

    /**
     * Load current layout and its configuration.
     *
     * @param string $name
     * @return Layout
     * @throws \LogicException
     */
    public function loadLayout($name = null)
    {
        if (!$name) {
            try {
                $name = static::gantry()['configuration'];
            } catch (\Exception $e) {
                throw new \LogicException('Gantry: Configuration has not been defined yet', 500);
            }
        }

        $layout = Layout::instance($name);

        if (!$layout->exists()) {
            $layout = Layout::instance('default');
        }

        return $layout;
    }

    public function add_to_context(array $context)
    {
        $gantry = static::gantry();

        $context['gantry'] = $gantry;
        $context['site'] = $gantry['site'];
        $context['theme'] = $this;

        return $context;
    }

    /**
     * Returns all non-empty segments from the layout.
     *
     * @return array
     */
    public function segments()
    {
        if (!isset($this->segments)) {
            $this->segments = $this->loadLayout()->toArray();
            $this->prepareLayout($this->segments);
        }

        return $this->segments;
    }

    public function add_to_twig(\Twig_Environment $twig, \Twig_Loader_Filesystem $loader = null)
    {
        $gantry = static::gantry();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        if (!$loader) {
            $loader = $twig->getLoader();
        }
        $loader->setPaths($locator->findResources('gantry-engine://templates'), 'nucleus');
        $loader->setPaths($locator->findResources('gantry-particles://'), 'particles');

        $twig->addExtension(new \Twig_Extension_Debug());
        $twig->addExtension(new TwigExtension);
        $twig->addFilter('toGrid', new \Twig_Filter_Function(array($this, 'toGrid')));
        return $twig;
    }

    /**
     * Returns details of the theme.
     *
     * @return ThemeDetails
     */
    public function details()
    {
        if (!$this->details) {
            $this->details = new ThemeDetails($this->name);
        }
        return $this->details;
    }

    /**
     * Returns configuration of the theme.
     *
     * @return array
     */
    public function configuration()
    {
        return (array) $this->details()['configuration'];
    }

    /**
     * Function to convert block sizes into CSS classes.
     *
     * @param $text
     * @return string
     */
    public function toGrid($text)
    {
        if (!$text) {
            return '';
        }

        $number = round($text, 1);
        $number = max(5, $number);
        $number = (string) ($number == 100 ? 100 : min(95, $number));

        static $sizes = array(
            '33.3' => 'size-33-3',
            '16.7' => 'size-16-7',
            '14.3' => 'size-14-3',
            '12.5' => 'size-12-5',
            '11.1' => 'size-11-1',
            '9.1'  => 'size-9-1',
            '8.3'  => 'size-8-3'
        );

        return isset($sizes[$number]) ? ' ' . $sizes[$number] : 'size-' . (int) $number;
    }

    /**
     * Magic setter method
     *
     * @param mixed $offset Asset name value
     * @param mixed $value  Asset value
     */
    public function __set($offset, $value)
    {
        if ($offset == 'title') {
            $offset = 'name';
        }

        $this->details()->offsetSet('details.' . $offset, $value);
    }

    /**
     * Magic getter method
     *
     * @param  mixed $offset Asset name value
     * @return mixed         Asset value
     */
    public function __get($offset)
    {
        if ($offset == 'title') {
            $offset = 'name';
        }

        $value = $this->details()->offsetGet('details.' . $offset);

        if ($offset == 'version' && is_int($value)) {
            $value .= '.0';
        }

        return $value;
    }

    /**
     * Magic method to determine if the attribute is set
     *
     * @param  mixed   $offset Asset name value
     * @return boolean         True if the value is set
     */
    public function __isset($offset)
    {
        if ($offset == 'title') {
            $offset = 'name';
        }

        return $this->details()->offsetExists('details.' . $offset);
    }

    /**
     * Magic method to unset the attribute
     *
     * @param mixed $offset The name value to unset
     */
    public function __unset($offset)
    {
        if ($offset == 'title') {
            $offset = 'name';
        }

        $this->details()->offsetUnset('details.' . $offset);
    }


    /**
     * Prepare layout by loading all the positions and particles.
     *
     * Action is needed before displaying the layout as it recalculates block widths based on the visible content.
     *
     * @param array $items
     * @internal
     */
    protected function prepareLayout(array &$items)
    {
        foreach ($items as $i => &$item) {
            // Non-numeric items are meta-data which should be ignored.
            if (((string)(int) $i !== (string) $i) || !is_object($item)) {
                continue;
            }
            if (!empty($item->children)) {
                $this->prepareLayout($item->children);
            }

            // TODO: remove hard coded types.
            switch ($item->type) {
                case 'pagecontent':
                    break;

                case 'atom':
                case 'particle':
                case 'position':
                case 'spacer':
                    $item->content = $this->renderContent($item);
                    if (!$item->content) {
                        unset($items[$i]);
                    }

                    break;

                default:
                    if (!$item->children) {
                        unset($items[$i]);
                        break;
                    }

                    $dynamicSize = 0;
                    $fixedSize = 0;
                    foreach ($item->children as $child) {
                        if (!isset($child->attributes->size)) {
                            $child->attributes->size = 100 / count($item->children);
                        }
                        $dynamicSize += $child->attributes->size;
                    }
                    if (round($dynamicSize, 1) != 100) {
                        $multiplier = (100 - $fixedSize) / $dynamicSize;
                        foreach ($item->children as $child) {
                            $child->attributes->size *= $multiplier;
                        }
                    }
            }
        }
    }

    /**
     * Renders individual content block, like particle or position.
     *
     * Function is used to pre-render content.
     *
     * @param object $item
     * @return string
     */
    protected function renderContent($item)
    {
        $context = $this->add_to_context(['segment' => $item]);

        return trim($this->render("@nucleus/content/{$item->type}.html.twig", $context));
    }
}
