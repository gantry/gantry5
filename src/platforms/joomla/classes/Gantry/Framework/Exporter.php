<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Content\ContentFinder;
use Gantry\Joomla\Module\ModuleFinder;
use Gantry\Joomla\StyleHelper;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Exporter
{
    protected $files = [];

    public function all()
    {
        $theme = Gantry::instance()['theme']->details();

        return [
            'export' => [
                'gantry' => [
                    'version' => GANTRY5_VERSION !== '@version@' ? GANTRY5_VERSION : 'GIT',
                    'format' => 1
                ],
                'platform' => [
                    'name' => 'joomla',
                    'version' => JVERSION
                ],
                'theme' => [
                    'name' => $theme->get('name'),
                    'title' => $theme->get('details.name'),
                    'version' => $theme->get('details.version'),
                    'date' => $theme->get('details.date'),
                    'author' => $theme->get('details.author'),
                    'copyright' => $theme->get('details.copyright'),
                    'license' => $theme->get('details.license'),
                ]
            ],
            'outlines' => $this->outlines(),
            'positions' => $this->positions(),
            'menus' => $this->menus(),
            'content' => $this->articles(),
            'categories' => $this->categories(),
            'files' => $this->files,
        ];
    }

    public function outlines()
    {
        $gantry = Gantry::instance();
        $styles = StyleHelper::loadStyles($gantry['theme.name']);

        $list = [
            'default' => ['title' => 'Default'],
            '_error' => ['title' => 'Error'],
            '_offline' => ['title' => 'Offline'],
            '_body_only' => ['title' => 'Body Only'],
        ];
        $inheritance = [];

        foreach ($styles as $style) {
            $name = $base = strtolower(trim(preg_replace('|[^a-z\d_-]+|ui', '_', $style->title), '_'));
            $i = 0;
            while (isset($list[$name])) {
                $i++;
                $name = "{$base}-{$i}";
            };
            $inheritance[$style->id] = $name;
            $list[$name] = [
                'id' => (int) $style->id,
                'title' => $style->title,
                'home' => $style->home,
            ];
            if (!$style->home) {
                unset($list[$name]['home']);
            }
        }

        foreach ($list as $name => &$style) {
            $id = isset($style['id']) ? $style['id'] : $name;
            $config = ConfigServiceProvider::load($gantry, $id, false, false);

            // Update layout inheritance.
            $layout = Layout::instance($id);
            $layout->name = $name;
            foreach ($inheritance as $from => $to) {
                $layout->updateInheritance($from, $to);
            }
            $style['preset'] = $layout->preset['name'];
            $config['index'] = $layout->buildIndex();
            $config['layout'] = $layout->export();

            // Update atom inheritance.
            $atoms = $config->get('page.head.atoms');
            if (is_array($atoms)) {
                $atoms = new Atoms($atoms);
                foreach ($inheritance as $from => $to) {
                    $atoms->updateInheritance($from, $to);
                }
                $config->set('page.head.atoms', $atoms->update()->toArray());
            }

            // Add assignments.
            if (is_numeric($id)) {
                $assignments = $this->getOutlineAssignments($id);
                if ($assignments) {
                    $config->set('assignments', $this->getOutlineAssignments($id));
                }
            }
            
            $style['config'] = $config->toArray();
        }

        return $list;
    }

    public function positions($all = true)
    {
        $gantry = Gantry::instance();
        $positions = $gantry['outlines']->positions();
        $positions['debug'] = 'Debug';

        $finder = new ModuleFinder();
        if (!$all) {
            $finder->particle();
        }
        $modules = $finder->find()->export();
        $list = [];
        foreach ($modules as $position => &$items) {
            if (!isset($positions[$position])) {
                continue;
            }
            foreach ($items as &$item) {
                $func = 'module' . $item['options']['type'];
                if (method_exists($this, $func)) {
                    $item = $this->{$func}($item);
                }
            }
            $list[$position] = [
                'title' => $positions[$position],
                'items' => $items,
            ];
        }

        return $list;
    }

    public function menus()
    {
        $gantry = Gantry::instance();
        $db = \JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select('id, menutype, title, description')
            ->from('#__menu_types');
        $db->setQuery($query);
        $menus = $db->loadObjectList('id');

        $list = [];
        foreach ($menus as $menu) {
            $items = $gantry['menu']->instance(['menu' => $menu->menutype])->items(false);

            array_walk(
                $items,
                function (&$item) {
                    $item['id'] = (int) $item['id'];
                    if (in_array($item['type'], ['component', 'alias'])) {
                        $item['type'] = "joomla.{$item['type']}";
                    }

                    unset($item['alias'], $item['path'], $item['parent_id'], $item['level']);
                }
            );

            $list[$menu->menutype] = [
                'id' => (int) $menu->id,
                'title' => $menu->title,
                'description' => $menu->description,
                'items' => $items
            ];
        }

        return $list;
    }

    public function articles()
    {
        $finder = new ContentFinder();

        $articles = $finder->limit(0)->find();

        $list = [];
        foreach ($articles as $article) {
            $exported = $article->toArray();

            // Convert images to use streams.
            $exported['introtext'] = $this->urlFilter($exported['introtext']);
            $exported['fulltext'] = $this->urlFilter($exported['fulltext']);

            $list[$article->id . '-' . $article->alias] = $exported;
        }

        return $list;
    }

    public function categories()
    {
        $finder = new CategoryFinder();

        $categories = $finder->limit(0)->find();

        $list = [];
        foreach ($categories as $category) {
            $list[$category->id] = $category->toArray();
        }

        return $list;
    }


    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function getOutlineAssignments($configuration)
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
        $app = \JApplicationCms::getInstance('site');
        $menu = $app->getMenu();
        $data = \MenusHelper::getMenuLinks();

        $items = [];
        foreach ($data as $item) {
            foreach ($item->links as $link) {
                if ($link->template_style_id == $configuration) {
                    $items[$menu->getItem($link->value)->route] = 1;
                }
            }
        }

        if ($items) {
            return ['page' => [$items]];
        }

        return [];
    }

    /**
     * Filter stream URLs from HTML.
     *
     * @param  string $html         HTML input to be filtered.
     * @return string               Returns modified HTML.
     */
    public function urlFilter($html)
    {
        // Tokenize all PRE and CODE tags to avoid modifying any src|href|url in them
        $tokens = [];
        $html = preg_replace_callback('#<(pre|code).*?>.*?<\\/\\1>#is', function($matches) use (&$tokens) {
            $token = uniqid('__g5_token');
            $tokens['#' . $token . '#'] = $matches[0];

            return $token;
        }, $html);

        $html = preg_replace_callback('^(\s)(src|href)="(.*?)"^', [$this, 'linkHandler'], $html);
        $html = preg_replace_callback('^(\s)url\((.*?)\)^', [$this, 'urlHandler'], $html);
        $html = preg_replace(array_keys($tokens), array_values($tokens), $html); // restore tokens

        return $html;
    }

    public function url($url)
    {
        // Only process local urls.
        if ($url === '' || $url[0] === '/' || $url[0] === '#') {
            return $url;
        }

        /** @var UniformResourceLocator $locator */
        $locator = Gantry::instance()['locator'];

        // Handle URIs.
        if (strpos($url, '://')) {
            if ($locator->isStream($url)) {
                // File is a stream, include it to files list.
                list ($stream, $path) = explode('://', $url);
                $this->files[$stream][$path] = $url;
            }

            return $url;
        }

        // Try to convert local paths to streams.
        $paths = $locator->getPaths();

        $found = false;
        $stream = $path = '';
        foreach ($paths as $stream => $prefixes) {
            foreach ($prefixes as $prefix => $paths) {
                foreach ($paths as $path) {
                    if (is_string($path) && strpos($url, $path) === 0) {
                        $path = ($prefix ? "{$prefix}/" : '') . substr($url, strlen($path) + 1);
                        $found = true;
                        break 3;
                    }
                }
            }
        }

        if ($found) {
            $url = "{$stream}://{$path}";
            $this->files[$stream][$path] = $url;
        }

        return $url;
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public function linkHandler(array $matches)
    {
        $url = $this->url(trim($matches[3]));

        return "{$matches[1]}{$matches[2]}=\"{$url}\"";
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public function urlHandler(array $matches)
    {
        $url = $this->url(trim($matches[2], '"\''));

        return "{$matches[1]}url({$url})";
    }

    protected function moduleMod_Custom(array $data)
    {
        // Convert to particle...
        $data['type'] = 'particle';
        $data['joomla'] = $data['options'];
        $data['options'] = [
            'type' => 'custom',
            'attributes' => [
                'enabled' => $data['joomla']['published'],
                'html' => $this->urlFilter($data['joomla']['content']),
                'filter' => $data['joomla']['params']['prepare_content']
            ]
        ];

        unset($data['joomla']['content'], $data['joomla']['params']['prepare_content']);

        return $data;
    }
}
