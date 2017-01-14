<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Url\Url;
use RocketTheme\Toolbox\File\MarkdownFile;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Importer
{
    protected $folder;

    protected $articles;
    protected $categories;

    /**
     * @var UniformResourceLocator
     */
    protected $locator;

    /**
     * Importer constructor.
     * @param $folder
     */
    public function __construct($folder)
    {
        /** @var UniformResourceLocator $locator */
        $this->locator = Gantry::instance()['locator'];

        $this->folder = $folder;
    }

    public function all()
    {
        $this->fetchArticles();
        //$this->files();
        $this->positions();
        $this->outlines();
        $this->menus();
        $this->content();
    }

    /**
     * Copy files.
     */
    public function files()
    {
        $files = Folder::all("{$this->folder}/files", ['folders' => false]);

        foreach ($files as $file) {
            $stream = preg_replace('|^([^/]+)|', '\\1:/', $file);

            copy("{$this->folder}/files/{$file}", $this->locator->findResource($stream, true, true));
        }
    }

    public function positions()
    {
        $folder = $this->locator->findResource('gantry-positions://', true, true);

        if (is_dir($folder)) Folder::delete($folder);
        Folder::copy("{$this->folder}/positions", $folder);
    }

    public function outlines()
    {
        $folder = $this->locator->findResource('gantry-theme://config', true, true);

        if (is_dir($folder)) Folder::delete($folder);
        Folder::copy("{$this->folder}/outlines", $folder);
    }

    public function menus()
    {
        $from = "{$this->folder}/menus";

        $config = $this->locator->findResource('gantry-theme://config/menus', true, true);
        if (is_dir($config)) Folder::delete($config);

        $pages = $this->locator->findResource('page://', true, true);
        if (is_dir($pages)) Folder::delete($pages);

        $files = Folder::all($from, ['folders' => false, 'recursive' => false]);

        foreach ($files as $filename) {
            $file = YamlFile::instance("{$from}/{$filename}");
            $menu = $file->content();
            $this->menu($menu);
        }

        //Folder::copy($from, $config);
    }

    public function content()
    {
        foreach ($this->articles as $id => $filename) {
            $article = $this->readArticle($id);
            if (!$article) {
                continue;
            }

            $foldername = sprintf('%02d.%s', $id, $article['alias']);
            $folder = $this->locator->findResource("page://category/{$article['category']}/{$foldername}", true, true);
            Folder::create($folder);

            $file = MarkdownFile::instance("{$folder}/blog_item.md");
            $file->header($article['header']);
            $file->markdown($article['content']);
            $file->save();
            $file->free();
        }
    }

    protected function menu(array $menu)
    {
        $config = new Config([]);

        foreach ($menu['items'] as $path => &$item) {
            $alias = trim(substr($path, strrpos($path, '/')), '/');
            $location = preg_replace('|/|', '/children/', $path);
            $children = substr($location, 0, strrpos($location, '/'));

            $ordering = $config->count($children, '/') + 1;
            $parent = $config->get(substr($children, 0, strrpos($children, '/')));

            $item['ordering'] = $ordering;
            $item['alias'] = $alias;
            $item['path'] = $path;

            switch ($item['type']) {
                case 'joomla.component':
                    $item['page'] = $this->createComponentPage($item);
                    break;
                case 'joomla.alias':
                    $item['page'] = $this->createAliasPage($item);
                    break;
                case 'url':
                    $item['alias'] = preg_replace('|[^a-z0-9-_]+|', '-', strtolower($item['title']));
                    $item['page'] = $this->createUrlPage($item);
                    break;
                case 'particle':
                    $item['page'] = $this->createParticlePage($item);
                    break;
                case 'separator':
                default:
                    $item['page'] = $this->createSeparatorPage($item);
            }

            $folder = sprintf('%s%02d.%s', (isset($parent['folder']) ? $parent['folder'] . '/' : ''), $item['ordering'], $item['alias']);
            $item['folder'] = $folder;

            $config->set($location, $item, '/');

        }

        foreach ($menu['items'] as $path => $menuitem) {
            $page = $menuitem['page'];
            $folder = $this->locator->findResource("page://{$menuitem['folder']}", true, true);
            Folder::create($folder);

            $file = MarkdownFile::instance("{$folder}/{$page['type']}.md");
            $file->header($page['header']);
            $file->markdown($page['content']);
            $file->save();
            $file->free();
        }
    }

    protected function fetchArticles()
    {
        if (!isset($this->articles)) {
            $from = "{$this->folder}/content";
            $this->articles = Folder::all($from, ['folders' => false, 'recursive' => false, 'key' => 'filename', 'value' => 'pathname', 'filters' => ['key' => 'intval']]);
            if (isset($this->articles[0])) {
                $file = YamlFile::instance($this->articles[0]);
                $this->categories = $file->content();
                $file->free();
                unset($this->articles[0]);
            } else {
                $this->categories = [];
            }
        }

        return $this->articles;
    }

    protected function getCategoryAlias($id)
    {
        return isset($this->categories[$id]['alias']) ? $this->categories[$id]['alias'] : null;
    }

    protected function getCategoryTitle($id)
    {
        return isset($this->categories[$id]['title']) ? $this->categories[$id]['title'] : null;
    }

    protected function readArticle($id)
    {
        if (!isset($this->articles[$id])) {
            return [];
        }

        $file = YamlFile::instance($this->articles[$id]);
        $content = $file->content();
        $file->free();

        $text = $this->urlFilter($content['introtext'] . ($content['fulltext'] ? "\n\n===\n\n" . $content['fulltext'] : ''));
        $twig = strpos($text, '{{ url(') && strpos($text, ') }}');

        $article = [
            'type' => 'default',
            'alias' => $content['alias'],
            'catid' => $content['catid'],
            'category' => $content['category']['alias'],
            'modified' => $content['modified'] !== '0000-00-00 00:00:00' ? $content['modified'] : null,
            'header' => [
                'title' => $content['title'],
                'author' => [
                    'username' => $content['author']['username'] ?: null,
                    'alias' => $content['created_by_alias'] ?: ($content['author']['realname'] ?: null)
                ],
                'date' => $content['created'] !== '0000-00-00 00:00:00' ? $content['created'] : null,
                'published' => $content['state'] == 1,
                'publish_date' => $content['publish_up'] !== '0000-00-00 00:00:00' ? $content['publish_up'] : null,
                'unpublish_date' => $content['publish_down'] !== '0000-00-00 00:00:00' ? $content['publish_down'] : null,
                'taxonomy' => [
                    'category' => $content['category']['title'] ?: null
                ],
                'process' => [
                    'markdown' => false,
                    'twig' => $twig
                ],
                'metadata' => [
                    'keywords' => $content['metakey'] ?: null,
                    'description' => $content['metadesc'] ?: null
                ]
            ],
            'content' => $text
        ];

        $article = $this->filterNull($article);

        return $article;
    }

    protected function createComponentPage(array $item)
    {
        $page = [];

        $link = Url::parse($item['link'], true);
        $vars = $link['vars'];
        if (isset($vars['option'])) {
            switch ($vars['option']) {
                case 'com_content':
                    switch ($vars['view']) {
                        case 'article':
                            $page = $this->readArticle($vars['id']);
                            unset($this->articles[$vars['id']]);
                            break;
                        case 'featured':
                            $page = [
                                'type' => 'blog_list',
                                'header' => [
                                    'content' => [
                                        'items' => [],
                                        'limit' => 5,
                                        'order' => [
                                            'by' => 'date',
                                            'dir' => 'desc'
                                        ],
                                        'pagination' => true,
                                        'url_taxonomy_filters' => true
                                    ],
                                    'pagination' => 1
                                ]
                            ];
                            break;
                        case 'category':
                            if ($vars['layout'] !== 'blog') {
                                die($link['query']);
                            }
                            $page = [
                                'type' => 'blog_list',
                                'header' => [
                                    'content' => [
                                        'items' => ['@taxonomy.category' => $this->getCategoryTitle($vars['id'])],
                                        'limit' => 5,
                                        'order' => [
                                            'by' => 'date',
                                            'dir' => 'desc'
                                        ],
                                        'pagination' => true,
                                        'url_taxonomy_filters' => true
                                    ],
                                    'pagination' => 1
                                ]
                            ];
                            break;
                        default:
                            die($link['query']);
                    }
                    break;
                case 'com_gantry5':
                    if ($vars['view'] === 'error') {
                        $page = [
                            'header' => [
                                'gantry' => [
                                    'outline' => '_error'
                                ],
                                'http_response_code' => 404
                            ],
                            'content' => "Whoops. Looks like this page doesn't exist."
                        ];
                    }
                    break;
                case 'com_contact':
                    $page = [
                        'header' => [
                            'cache_enable' => false,
                            'process' => [
                                'markdown' => true,
                                'twig' => true
                            ]
                        ],
                        'content' => "## Contact Form

{% include \"forms/form.html.twig\" with {form: forms( {route: '/form/contact'} )} %}"
                    ];
                    break;
                case 'com_search':
                    // TODO:
                    break;
                default:
                    die($link['query']);
            }
        }

        $page += [
            'type' => 'default',
            'header' => [],
            'content' => ''
        ];
        $page['header'] += [
            'menu' => $item['title'],
            'title' => $item['title']
        ];

        if ($page['header']['menu'] === $page['header']['title']) {
            unset($page['header']['menu']);
        }

        return $page;
    }

    protected function createAliasPage(array $item)
    {
        return [
            'type' => 'default',
            'header' => [
                'title' => $item['title'],
            ],
            'content' => ''
        ];
    }

    protected function createUrlPage(array $item)
    {
        return [
            'type' => 'default',
            'header' => [
                'menu' => $item['title'],
                'external_url' => $item['link']
            ],
            'content' => ''
        ];
    }

    protected function createSeparatorPage(array $item)
    {
        return [
            'type' => 'default',
            'header' => [
                'menu' => $item['title'],
                'routable' => false
            ],
            'content' => ''
        ];
    }


    protected function createParticlePage(array $item)
    {
        return [
            'type' => 'default',
            'header' => [
                'menu' => $item['title'],
                'routable' => false
            ],
            'content' => ''
        ];
    }

    protected function filterNull($v)
    {
        if (is_array($v)) {
            foreach ($v as $key => $value) {
                $value = $this->filterNull($value);
                if (is_null($value) || (is_array($value) && empty($value))) {
                    unset($v[$key]);
                } else {
                    $v[$key] = $value;
                }
            }
        }

        return $v;
    }

    /**
     * Filter stream URLs from HTML.
     *
     * @param  string $html         HTML input to be filtered.
     * @param  bool $domain         True to include domain name.
     * @param  int $timestamp_age   Append timestamp to files that are less than x seconds old. Defaults to a week.
     *                              Use value <= 0 to disable the feature.
     * @return string               Returns modified HTML.
     */
    protected function urlFilter($html)
    {
        // Tokenize all PRE and CODE tags to avoid modifying any src|href|url in them
        $tokens = [];
        $html = preg_replace_callback('#<(pre|code).*?>.*?<\\/\\1>#is', function($matches) use (&$tokens) {
            $token = uniqid('__g5_token');
            $tokens['#' . $token . '#'] = $matches[0];

            return $token;
        }, $html);

        $html = preg_replace_callback('^(\s)url\((.*?)\)^', 'static::urlHandler', $html);
        $html = preg_replace_callback('^(\s)(src|href)="(.*?)"^', 'static::linkHandler', $html);
        $html = preg_replace(array_keys($tokens), array_values($tokens), $html); // restore tokens

        return $html;
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public static function linkHandler(array $matches)
    {
        $url = trim($matches[3]);

        return "{$matches[1]}{$matches[2]}=\"{{ url('{$url}') }}\"";
    }

    /**
     * @param array $matches
     * @return string
     * @internal
     */
    public static function urlHandler(array $matches)
    {
        $url = trim($matches[2], '"\'');

        return "{$matches[1]}url({{ url('{$url}') }})";
    }
}
