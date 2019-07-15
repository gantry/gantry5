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

namespace Gantry\Component\Theme;

use Gantry\Component\Config\Config;
use Gantry\Component\Content\Block\ContentBlock;
use Gantry\Component\Content\Block\HtmlBlock;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Stylesheet\CssCompilerInterface;
use Gantry\Framework\Document;
use Gantry\Framework\Menu;
use Gantry\Framework\Services\ConfigServiceProvider;
use RocketTheme\Toolbox\File\PhpFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class ThemeTrait
 * @package Gantry\Component
 *
 * @property string $path
 * @property string $layout
 */
trait ThemeTrait
{
    use GantryTrait;

    protected $layoutObject;
    protected $atoms;
    protected $segments;
    protected $preset;
    protected $cssCache;
    /**
     * @var CssCompilerInterface
     */
    protected $compiler;
    protected $equalized = [3 => 33.3, 6 => 16.7, 7 => 14.3, 8 => 12.5, 9 => 11.1, 11 => 9.1, 12 => 8.3];

    /**
     * @var ThemeDetails
     */
    protected $details;

    /**
     * Register Theme stream.
     *
     * @param string $savePath
     */
    public function registerStream($savePath = null)
    {
        $streamName = $this->details()->addStreams();

        /** @var UniformResourceLocator $locator */
        $locator = self::gantry()['locator'];
        $locator->addPath('gantry-theme', '', array_merge((array) $savePath, [[$streamName, '']]));
    }

    /**
     * Update all CSS files in the theme.
     *
     * @param array $outlines
     * @return array List of CSS warnings.
     */
    public function updateCss(array $outlines = null)
    {
        $gantry = static::gantry();
        $compiler = $this->compiler();

        if (is_null($outlines)) {
            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            $path = $locator->findResource($compiler->getTarget(), true, true);

            // Make sure that all the CSS files get deleted.
            if (is_dir($path)) {
                Folder::delete($path, false);
            }

            $outlines = $gantry['outlines'];
        }

        // Make sure that PHP has the latest data of the files.
        clearstatcache();

        $warnings = [];
        foreach ($outlines as $outline => $title) {
            $config = ConfigServiceProvider::load($gantry, $outline);

            $compiler->reset()->setConfiguration($outline)->setVariables($config->flatten('styles', '-'));

            $results = $compiler->compileAll()->getWarnings();
            if ($results) {
                $warnings[$outline] = $results;
            }
        }

        return $warnings;
    }

    /**
     * Set layout to be used.
     *
     * @param string $name
     * @param bool $force
     * @return $this
     */
    public function setLayout($name = null, $force = false)
    {
        $gantry = static::gantry();

        // Force new layout to be set.
        if ($force) {
            unset($gantry['configuration']);
        }

        // Set default name only if configuration has not been set before.
        if ($name === null && !isset($gantry['configuration'])) {
            $name = 'default';
        }

        $outline = isset($gantry['configuration']) ? $gantry['configuration'] : null;

        // Set configuration if given.
        if ($name && $name != $outline) {
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage("Using Gantry outline {$name}");

            $gantry['configuration'] = $name;
        }

        return $this;
    }

    /**
     * Get current preset.
     *
     * @param  bool $forced     If true, return only forced preset or null.
     * @return string|null $preset
     */
    public function preset($forced = false)
    {
        $presets = $this->presets()->toArray();

        $preset = $this->preset;

        if (!$preset && !$forced) {
            $preset = static::gantry()['config']->get('styles.preset', '-undefined-');
        }

        if ($preset && !isset($presets[$preset])) {
            $preset = null;
        }

        return $preset;
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
        if (!$this->compiler) {
            $compilerClass = (string) $this->details()->get('configuration.css.compiler', '\Gantry\Component\Stylesheet\ScssCompiler');

            if (!class_exists($compilerClass)) {
                throw new \RuntimeException('CSS compiler used by the theme not found');
            }

            $details = $this->details();

            /** @var CssCompilerInterface $compiler */
            $this->compiler = new $compilerClass();
            $this->compiler
                ->setTarget($details->get('configuration.css.target'))
                ->setPaths($details->get('configuration.css.paths'))
                ->setFiles($details->get('configuration.css.files'))
                ->setFonts($details->get('configuration.fonts'));
        }

        $preset = $this->preset(true);
        if ($preset) {
            $this->compiler->setConfiguration($preset);
        } else {
            $gantry = static::gantry();
            $this->compiler->setConfiguration(isset($gantry['configuration']) ? $gantry['configuration'] : 'default');
        }

        return $this->compiler->reset();
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
        if (!isset($this->cssCache[$name])) {
            $compiler = $this->compiler();

            if ($compiler->needsCompile($name, [$this, 'getCssVariables'])) {
                GANTRY_DEBUGGER && \Gantry\Debugger::startTimer("css-{$name}", "Compiling CSS: {$name}") && \Gantry\Debugger::addMessage("Compiling CSS: {$name}");

                $compiler->compileFile($name);

                GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer("css-{$name}");
            }

            $this->cssCache[$name] = $compiler->getCssUrl($name);
        }

        return $this->cssCache[$name];
    }

    public function getCssVariables()
    {
        if ($this->preset) {
            $variables = $this->presets()->flatten($this->preset . '.styles', '-');
        } else {
            $gantry = self::gantry();
            $variables = $gantry['config']->flatten('styles', '-');
        }

        return $variables;
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
            $file = CompiledYamlFile::instance($filename);
            $presets = new Config($file->content());
            $file->free();
        }

        return $presets;
    }

    /**
     * Return name of the used layout preset.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function type()
    {
        if (!$this->layoutObject) {
            throw new \RuntimeException('Function called too early');
        }
        $name = isset($this->layoutObject->preset['name']) ? $this->layoutObject->preset['name'] : 'unknown';

        return $name;
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
                throw new \LogicException('Gantry: Outline has not been defined yet', 500);
            }
        }

        if (!isset($this->layoutObject) || $this->layoutObject->name != $name) {
            $layout = Layout::instance($name);

            if (!$layout->exists()) {
                $layout = Layout::instance('default');
            }

            // TODO: Optimize
            $this->layoutObject = $layout->init();
        }

        return $this->layoutObject;
    }

    /**
     * Check whether layout has content bock.
     *
     * @return bool
     */
    public function hasContent()
    {
        $layout = $this->loadLayout();
        $content = $layout->referencesByType('system', 'content');

        return !empty($content);
    }

    /**
     * Load atoms and assets from the page settings.
     *
     * @since 5.4.9
     */
    public function loadAtoms()
    {
        if (!isset($this->atoms)) {
            $this->atoms = true;

            GANTRY_DEBUGGER && \Gantry\Debugger::startTimer('atoms', "Preparing atoms");

            $gantry = static::gantry();

            /** @var Config $config */
            $config = $gantry['config'];

            /** @var \Gantry\Framework\Document $document */
            $document = $gantry['document'];

            $atoms = (array) $config->get('page.head.atoms');

            foreach ($atoms as $data) {
                $atom = [
                    'type' => 'atom',
                    'subtype' => $data['type'],
                ] + $data;

                try {
                    $block = $this->getContent($atom);
                    $document->addBlock($block);

                } catch (\Exception $e) {
                    if ($gantry->debug()) {
                        throw new \RuntimeException("Rendering Atom '{$atom['subtype']}' failed on error: {$e->getMessage()}", 500, $e);
                    }
                }
            }

            $assets = (array) $config->get('page.assets');

            if ($assets) {
                $atom = [
                    'id' => 'page-assets',
                    'title' => 'Page Assets',
                    'type' => 'atom',
                    'subtype' => 'assets',
                    'attributes' => $assets + ['enabled' => 1]
                ];

                try {
                    $block = $this->getContent($atom);
                    $document->addBlock($block);

                } catch (\Exception $e) {
                    if ($gantry->debug()) {
                        throw new \RuntimeException("Rendering CSS/JS Assets failed on error: {$e->getMessage()}", 500, $e);
                    }
                }
            }

            GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer('atoms');
        }
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

            GANTRY_DEBUGGER && \Gantry\Debugger::startTimer('segments', "Preparing layout");

            $this->prepareLayout($this->segments);

            GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer('segments');
        }

        return $this->segments;
    }

    /**
     * Prepare layout for rendering. Initializes all CSS/JS in particles.
     */
    public function prepare()
    {
        $this->segments();
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
     * @param bool  $temporary
     * @param bool  $sticky
     * @internal
     */
    protected function prepareLayout(array &$items, $temporary = false, $sticky = false)
    {
        foreach ($items as $i => &$item) {
            // Non-numeric items are meta-data which should be ignored.
            if (((string)(int) $i !== (string) $i) || !is_object($item)) {
                continue;
            }

            if (!empty($item->children)) {
                $fixed = true;
                foreach ($item->children as $child) {
                    $fixed &= !empty($child->attributes->fixed);
                }

                $this->prepareLayout($item->children, $fixed, $temporary);
            }

            // TODO: remove hard coded types.
            switch ($item->type) {
                case 'system':
                    break;

                case 'atom':
                case 'particle':
                case 'position':
                case 'spacer':
                    GANTRY_DEBUGGER && \Gantry\Debugger::startTimer($item->id, "Rendering {$item->id}");

                    $item->content = $this->renderContent($item, ['prepare_layout' => true]);
                    // Note that content can also be null (postpone rendering).
                    if ($item->content === '') {
                        unset($items[$i]);
                    }

                    GANTRY_DEBUGGER && \Gantry\Debugger::stopTimer($item->id);

                    break;

                default:
                    if ($sticky) {
                        $item->attributes->sticky = 1;
                        break;
                    }

                    if (empty($item->children)) {
                        unset($items[$i]);
                        break;
                    }

                    $dynamicSize = 0;
                    $fixedSize = 0;
                    $childrenCount = count($item->children);
                    foreach ($item->children as $child) {
                        if (!isset($child->attributes->size)) {
                            $child->attributes->size = 100 / count($item->children);
                        }
                        if (empty($child->attributes->fixed)) {
                            $dynamicSize += $child->attributes->size;
                        } else {
                            $fixedSize += $child->attributes->size;
                        }
                    }

                    $roundSize = round($dynamicSize, 1);
                    $equalized = isset($this->equalized[$childrenCount]) ? $this->equalized[$childrenCount] : 0;

                    // force-casting string for testing comparison due to weird PHP behavior that returns wrong result
                    if ($roundSize != 100 && (string) $roundSize != (string) ($equalized * $childrenCount)) {
                        $fraction = 0;
                        $multiplier = (100 - $fixedSize) / ($dynamicSize ?: 1);
                        foreach ($item->children as $child) {
                            if (!empty($child->attributes->fixed)) {
                                continue;
                            }

                            // Calculate size for the next item by taking account the rounding error from the last item.
                            // This will allow us to approximate cumulating error and fix it when rounding error grows
                            // over the rounding treshold.
                            $size = ($child->attributes->size * $multiplier) + $fraction;
                            $newSize = round($size);
                            $fraction = $size - $newSize;
                            $child->attributes->size = $newSize;
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
     * @param object|array $item
     * @param array $options
     * @return string|null
     */
    public function renderContent($item, $options = [])
    {
        $gantry = static::gantry();

        $content = $this->getContent($item, $options);

        /** @var Document $document */
        $document = $gantry['document'];
        $document->addBlock($content);

        $html = $content->toString();
        return !strstr($html, '@@DEFERRED@@') ? $html : null;
    }

    /**
     * Renders individual content block, like particle or position.
     *
     * Function is used to pre-render content.
     *
     * @param object|array $item
     * @param array $options
     * @return ContentBlock
     * @since 5.4.3
     */
    public function getContent($item, $options = [])
    {
        if (is_array($item)) {
            $item = (object) $item;
        }

        $gantry = static::gantry();

        /** @var Config $global */
        $global = $gantry['global'];

        $production = (bool) $global->get('production');
        $subtype = $item->subtype;
        $enabled = $gantry['config']->get("particles.{$subtype}.enabled", 1);

        if (!$enabled) {
            return new HtmlBlock;
        }

        $particle = $gantry['config']->getJoined("particles.{$subtype}", $item->attributes);

        $cached = false;
        $cacheKey = [];

        // Enable particle caching only in production mode.
        if ($production && isset($particle['caching'])) {
            $caching = $particle['caching'] + ['type' => 'dynamic'];

            switch ($caching['type']) {
                case 'static':
                    $cached = true;
                    break;
                case 'config_matches':
                    if (isset($particle['caching']['values'])) {
                        $values = (array) $particle['caching']['values'];
                        $compare = array_intersect_key($particle, $values);
                        $cached = ($values === $compare);
                    }
                    break;
                case 'menu':
                    /** @var Menu $menu */
                    $menu = $gantry['menu'];
                    $cacheId = $menu->getCacheId();

                    // FIXME: menu caching needs to handle dynamic modules inside menu: turning it off for now.
                    if (false && $cacheId !== null) {
                        $cached = true;
                        $cacheKey['menu_cache_key'] = $cacheId;
                    }
                    break;
            }
        }

        if ($cached) {
            $cacheKey['language'] = $gantry['page']->language;
            $cacheKey['attributes'] = $particle;
            $cacheKey += (array) $item;

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];
            $key = md5(json_encode($cacheKey));

            $filename = $locator->findResource("gantry-cache://theme/html/{$key}.php", true, true);
            $file = PhpFile::instance($filename);
            if ($file->exists()) {
                try {
                    return ContentBlock::fromArray((array) $file->content());
                } catch (\Exception $e) {
                    // Invalid cache, continue to rendering.
                    GANTRY_DEBUGGER && \Gantry\Debugger::addMessage(sprintf('Failed to load %s %s cache', $item->type, $item->id), 'debug');
                }
            }
        }

        // Create new document context for assets.
        $context = $this->getContext(['segment' => $item, 'enabled' => 1, 'particle' => $particle] + $options);

        /** @var Document $document */
        $document = $gantry['document'];
        $document->push();
        $html = trim($this->render("@nucleus/content/{$item->type}.html.twig", $context));
        $content = $document->pop()->setContent($html);

        if (isset($file)) {
            // Save HTML and assets into the cache.
            GANTRY_DEBUGGER && \Gantry\Debugger::addMessage(sprintf('Caching %s %s', $item->type, $item->id), 'debug');
            $file->save($content->toArray());
        }

        return $content;
    }
}
