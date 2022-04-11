<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Category\CategoryFinder;
use Gantry\Joomla\Contact\ContactFinder;
use Gantry\Joomla\Content\Content;
use Gantry\Joomla\Content\ContentFinder;
use Gantry\Joomla\MenuItem\MenuItemFinder;
use Gantry\Joomla\Module\ModuleFinder;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * @package Gantry\Framework
 */
class Exporter
{
    protected $files = [];

    /**
     * @return array
     */
    public function all()
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];
        $details = $theme->details();

        $export = [
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
                    'name' => $details->get('name'),
                    'title' => $details->get('details.name'),
                    'version' => $details->get('details.version'),
                    'date' => $details->get('details.date'),
                    'author' => $details->get('details.author'),
                    'copyright' => $details->get('details.copyright'),
                    'license' => $details->get('details.license'),
                    'updates' => $details->get('details.updates.server'),
                ]
            ],
            'outlines' => $this->outlines(),
            'positions' => $this->positions(),
            'menus' => $this->menus(),
            'content' => $this->articles(),
            'categories' => $this->categories(),
            'files' => $this->files
        ];

        $export['joomla']['mysql'] = $this->customSql($export);

        return $export;
    }

    /**
     * @return array
     */
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
            $i = 1;
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
            if (\is_array($atoms)) {
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

    /**
     * @param bool $all
     * @return array
     */
    public function positions($all = true)
    {
        $gantry = Gantry::instance();

        /** @var Outlines $outlines */
        $outlines = $gantry['outlines'];
        $positions = $outlines->positions();
        $positions['debug'] = 'Debug';

        $finder = new ModuleFinder();
        if (!$all) {
            $finder->particle();
        }
        /** @var array $modules */
        $modules = $finder->limit(0)->find()->export();
        $list = [];
        /** @var array $items */
        foreach ($modules as $position => &$items) {
            if (!isset($positions[$position])) {
                continue;
            }
            foreach ($items as &$item) {
                $method = 'module' . $item['options']['type'];
                if (method_exists($this, $method)) {
                    $item = $this->{$method}($item);
                }
            }
            unset($item);

            $list[$position] = [
                'title' => $positions[$position],
                'items' => $items,
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    public function menus()
    {
        $gantry = Gantry::instance();

        /** @var Menu $menu */
        $menu = $gantry['menu'];

        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('id, menutype, title, description')
            ->from('#__menu_types');
        $db->setQuery($query);

        /** @var array $menuList */
        $menuList = $db->loadObjectList('id');

        $list = [];
        foreach ($menuList as $menuItem) {
            $items = $menu->instance(['menu' => $menuItem->menutype])->items(false);

            array_walk(
                $items,
                function (&$item) {
                    $item['id'] = (int) $item['id'];
                    if (\in_array($item['type'], ['component', 'alias'], true)) {
                        $item['type'] = "joomla.{$item['type']}";
                    }

                    unset($item['alias'], $item['path'], $item['parent_id'], $item['level']);
                }
            );

            $list[$menuItem->menutype] = [
                'id' => (int) $menuItem->id,
                'title' => $menuItem->title,
                'description' => $menuItem->description,
                'items' => $items
            ];
        }

        return $list;
    }

    /**
     * @return array
     */
    public function articles()
    {
        $finder = new ContentFinder();

        $articles = $finder->limit(0)->find();

        $list = [];
        /** @var Content $article */
        foreach ($articles as $article) {
            $exported = $article->toArray();

            // Convert images to use streams.
            $exported['introtext'] = $this->urlFilter($exported['introtext']);
            $exported['fulltext'] = $this->urlFilter($exported['fulltext']);

            $list[$article->id . '-' . $article->alias] = $exported;
        }

        return $list;
    }

    /**
     * @return array
     */
    public function categories()
    {
        $finder = new CategoryFinder();

        $categories = $finder->limit(0)->find();

        $list = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            $list[$category->id] = $category->toArray();
        }

        return $list;
    }

    /**
     * @param array $details
     * @return string
     */
    public function customSql(array $details)
    {
        //return str_replace('#__', 'jos_', $this->dumpInstallSql($details));
        return $this->dumpInstallSql($details);
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function getOutlineAssignments($configuration)
    {
        $app = CMSApplication::getInstance('site');
        $menu = $app->getMenu();

        // Works also in Joomla 4
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';

        $data = (array)\MenusHelper::getMenuLinks();

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
        $temp = preg_replace_callback('#<(pre|code).*?>.*?</\\1>#is', function($matches) use (&$tokens) {
            $token = uniqid('__g5_token', false);
            $tokens['#' . $token . '#'] = $matches[0];

            return $token;
        }, $html);

        $temp = preg_replace_callback('^(\s)(src|href)="(.*?)"^', [$this, 'linkHandler'], $temp);
        $temp = preg_replace_callback('^(\s)url\((.*?)\)^', [$this, 'urlHandler'], $temp);
        $temp = preg_replace(array_keys($tokens), array_values($tokens), $temp); // restore tokens

        // Fall back to original input if ran into errors.
        return null !== $temp ? $temp : $html;
    }

    /**
     * @param string $url
     * @return string
     */
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
        /** @var array $prefixes */
        foreach ($paths as $stream => $prefixes) {
            /** @var array $paths */
            foreach ($prefixes as $prefix => $paths) {
                foreach ($paths as $path) {
                    if (\is_string($path) && strpos($url, $path) === 0) {
                        $path = ($prefix ? "{$prefix}/" : '') . substr($url, \strlen($path) + 1);
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

    /**
     * @param array $data
     * @return array
     */
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

    /**
     * @param array $export
     * @return string
     */
    protected function dumpInstallSql(array $export)
    {
        $theme = $export['export']['theme'];
        $themeName = $theme['name'];
        $themeTitle = $theme['title'];

        # Install Gantry package and theme.
        $out = <<<EOS
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

# SQL generated for Joomla 3.10 and 4.x

SELECT @theme_name := '{$themeName}';
SELECT @theme_title := '{$themeTitle} Template';
SELECT @theme_server := '{$theme['updates']}';

TRUNCATE `#__menu`;
TRUNCATE `#__menu_types`;
TRUNCATE `#__categories`;
TRUNCATE `#__content`;
TRUNCATE `#__contact_details`;

EOS;

        $out .= $this->installSql;
        if ($theme['updates']) {
            $out .= $this->themeUpdateSql;
        }
        $out .= $this->dumpOutlinesSql($export, $themeTitle);
        $out .= $this->dumpMenusSql($export);
        $out .= $this->dumpModulesSql();
        $out .= $this->dumpContentSql();
        $out .= "\n";

        return $out;
    }

    protected $installSql = <<<EOS

# Install Gantry package

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`) VALUES
(0,'pkg_gantry5','package','pkg_gantry5','',0,1,1,0,'','{}','');

SELECT @package_id := LAST_INSERT_ID();

# Install Gantry extensions

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`) VALUES
(@package_id,'plg_system_gantry5','plugin','gantry5','system',0,1,1,0,'','{}',''),
(@package_id,'plg_quickicon_gantry5','plugin','gantry5','quickicon',0,1,1,0,'','{}',''),
(@package_id,'plg_gantry5_preset','plugin','preset','gantry5',0,1,1,0,'','{\"preset\":\"presets\",\"reset\":\"reset-settings\"}',''),
(@package_id,'mod_gantry5_particle','module','mod_gantry5_particle','',0,1,0,0,'','{\"cache\":\"1\",\"cache_time\":\"900\",\"cachemode\":\"static\"}',''),
(@package_id,'gantry5_nucleus','file','gantry5_nucleus','',0,1,0,0,'','{}',''),
(@package_id,'com_gantry5','component','com_gantry5','',1,1,0,0,'','{}',''),
(@package_id,'Gantry 5 Framework','library','gantry5','',0,1,1,0,'','{}','');

# Install Gantry theme

INSERT INTO `#__extensions` (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `manifest_cache`, `params`, `custom_data`) VALUES
(0,@theme_name,'template',@theme_name,'',0,1,1,0,'','{}','');

SELECT @theme_id := LAST_INSERT_ID();

# Update sites

INSERT INTO `#__update_sites` (`name`, `type`, `location`, `enabled`,  `extra_query`) VALUES
('Gantry 5','extension','http://updates.gantry.org/5.0/joomla/pkg_gantry5.xml',1,'');
INSERT INTO `#__update_sites_extensions` (`update_site_id`, `extension_id`) VALUES (LAST_INSERT_ID(),@package_id);

INSERT INTO `#__update_sites` (`name`, `type`, `location`, `enabled`, `extra_query`) VALUES
('Gantry 5','collection','http://updates.gantry.org/5.0/joomla/list.xml',1,'');
INSERT INTO `#__update_sites_extensions` (`update_site_id`, `extension_id`) VALUES (LAST_INSERT_ID(),@package_id);

EOS;

    protected $themeUpdateSql = <<<EOS
# Update site for theme

INSERT INTO `#__update_sites` (`name`, `type`, `location`, `enabled`, `extra_query`) VALUES
(@theme_title,'extension',@theme_server,1,'');
INSERT INTO `#__update_sites_extensions` (`update_site_id`, `extension_id`) VALUES (LAST_INSERT_ID(),@theme_id);

EOS;

    protected function dumpOutlinesSql(array $export, $themeTitle)
    {
        $outlines = [];
        foreach ($export['outlines'] as $outline) {
            $id = isset($outline['id']) ? $outline['id'] : null;
            if (!$id) {
                continue;
            }

            $home = !empty($outline['home']) ? '1' : '0';
            $outlineTitle = $outline['title'];
            $outlinePreset = $outline['preset'];

            $outlines[] = "({$id},@theme_name,0,'{$home}','{$themeTitle} - {$outlineTitle}',0,'','{\"configuration\":\"{$id}\",\"preset\":\"{$outlinePreset}\"}')";
        }

        $out = '';
        if ($outlines) {
            $out .= "\n# Template styles\n\n";
            $out .= 'UPDATE `#__template_styles` SET `home` = 0 WHERE `client_id` = 0;' . "\n";
            $out .= 'INSERT INTO `#__template_styles` (`id`, `template`, `client_id`, `home`, `title`, `inheritable`, `parent`, `params`) VALUES';
            $out .= "\n" . implode(",\n", $outlines) . ';';
        }

        return $out;
    }

    protected function dumpMenusSql(array $export)
    {
        $menus = [];
        foreach ($export['menus'] as $menuType => $menu) {
            $id = isset($menu['id']) ? $menu['id'] : null;
            if (!$id) {
                continue;
            }

            $menuTitle = $menu['title'];
            $menuDescription = $menu['description'];

            $menus[] = "({$id},0,'{$menuType}','{$menuTitle}','{$menuDescription}',0)";
        }

        $out = '';
        if ($menus) {
            $out .= "\n\n# Menus\n\n";
            $out .= 'INSERT INTO `#__menu_types` (`id`, `asset_id`, `menutype`, `title`, `description`, `client_id`) VALUES';
            $out .= "\n" . implode(",\n", $menus) . ';';
            $out .= "\n\n# Menu Items\n\n";

            $finder = new MenuItemFinder();

            $out .= $finder->limit(0)->find()->exportSql();
        }

        return $out;
    }

    protected function dumpModulesSql()
    {
        $finder = new ModuleFinder();

        return $finder->limit(0)->find()->exportSql();
    }

    protected function dumpContentSql()
    {
        $categories = new CategoryFinder();
        $content = new ContentFinder();
        $contacts = new ContactFinder();

        $out = "\n\n# Categories\n";
        $out .= $categories->limit(0)->find()->exportSql();
        $out .= "\n\n# Articles\n";
        $out .= $content->limit(0)->find()->exportSql();
        $out .= "\n\n# Contacts\n";
        $out .= $contacts->limit(0)->find()->exportSql();

        return $out;
    }
}
