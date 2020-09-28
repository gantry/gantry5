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

use Gantry\Component\Filesystem\Folder;
use Gantry\Component\System\Messages;
use Gantry\Framework\Base\Platform as BasePlatform;
use Gantry\WordPress\PostQuery;
use Gantry\WordPress\Utilities;
use Gantry\WordPress\Widgets;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */
class Platform extends BasePlatform
{
    protected $name = 'wordpress';
    protected $features = ['widgets' => true];
    protected $file = 'gantry5/gantry5.php';

    public function __construct(Container $container)
    {
        $this->content_dir = Folder::getRelativePath(WP_CONTENT_DIR);
        $this->includes_dir = Folder::getRelativePath(ABSPATH . WPINC);
        $this->upload_dir = Folder::getRelativePath(wp_upload_dir()['basedir']);
        $this->gantry_dir = Folder::getRelativePath(GANTRY5_PATH);
        $this->multisite = get_current_blog_id() !== 1 ? '/blog-' . get_current_blog_id() : '';

        parent::__construct($container);

        /**
         * Please remember to add the newly added streams to the add_gantry5_streams_to_kses()
         * in gantry5.php so they would get added to the allowed kses protocols.
         */

        // Add wp-includes directory to the streams
        $this->items['streams']['wp-includes'] = ['type' => 'ReadOnlyStream', 'prefixes' => ['' => $this->includes_dir]];

        // Add wp-content directory to the streams
        $this->items['streams']['wp-content'] = ['type' => 'ReadOnlyStream', 'prefixes' => ['' => $this->content_dir]];
    }

    public function init()
    {
        // Support linked sample data.
        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : null;
        if ($theme && is_dir(WP_CONTENT_DIR . "/gantry5/{$theme}/media-shared")) {
            $custom = WP_CONTENT_DIR . "/gantry5/{$theme}/custom";
            if (!is_dir($custom)) {
                // First run -- copy configuration into a single location.
                $shared = WP_CONTENT_DIR . "/gantry5/{$theme}/theme-shared";
                $demo = WP_CONTENT_DIR . "/gantry5/{$theme}/theme-demo";

                try {
                    Folder::create($custom);
                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf("Failed to create folder '%s'.", $custom), 500, $e);
                }

                if (is_dir("{$shared}/custom/config")) {
                    Folder::copy("{$shared}/custom/config", "{$custom}/config");
                }
                if (is_dir("{$demo}/custom/config")) {
                    Folder::copy("{$demo}/custom/config", "{$custom}/config");
                }
            }
            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "wp-content://gantry5/{$theme}/theme-shared");
            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "wp-content://gantry5/{$theme}/theme-demo");
            array_unshift($this->items['streams']['gantry-theme']['prefixes'][''], "wp-content://gantry5/{$theme}/custom");
        }

        if ($this->multisite) {
            $theme = $this->get('streams.gantry-theme.prefixes..0');
            if ($theme) {
                $this->set('streams.gantry-theme.prefixes..0', $theme . $this->multisite);
            }
        }

        return parent::init();
    }

    public function getCachePath()
    {
        $global = $this->container['global'];

        return $global->get('cache_path') ?: WP_CONTENT_DIR . '/cache/gantry5' . $this->multisite;
    }

    public function getThemesPaths()
    {
        return ['' => Folder::getRelativePath(get_theme_root())];
    }

    public function getMediaPaths()
    {
        $paths = [$this->upload_dir];

        // Support linked sample data.
        $theme = isset($this->container['theme.name']) ? $this->container['theme.name'] : null;
        if ($theme && is_dir(WP_CONTENT_DIR . "/gantry5/{$theme}/media-shared")) {
            array_unshift($paths, "wp-content://gantry5/{$theme}/media-shared");
            array_unshift($paths, "wp-content://gantry5/{$theme}/media-demo");
        }

        if ($this->container['global']->get('use_media_folder', false)) {
            array_push($paths, 'gantry-theme://images');
        } else {
            array_unshift($paths, 'gantry-theme://images');
        }

        return ['' => $paths];
    }

    public function getEnginesPaths()
    {
        if (is_link(GANTRY5_PATH . '/engines')) {
            // Development environment.
            return ['' => [$this->gantry_dir . "/engines/{$this->name}", $this->gantry_dir . '/engines/common']];
        }

        return ['' => [$this->gantry_dir . '/engines']];
    }

    public function getAssetsPaths()
    {
        if (is_link(GANTRY5_PATH . '/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', $this->gantry_dir . "/assets/{$this->name}", $this->gantry_dir . '/assets/common']];
        }

        return ['' => ['gantry-theme://', $this->gantry_dir . '/assets']];
    }

    /**
     * Get preview url for individual theme.
     *
     * @param string $theme
     * @return null
     */
    public function getThemePreviewUrl($theme)
    {
        $gantry = Gantry::instance();

        return $gantry['document']->url('wp-admin/customize.php?theme=' . $theme);
    }

    /**
     * Get administrator url for individual theme.
     *
     * @param string $theme
     * @return string|null
     */
    public function getThemeAdminUrl($theme)
    {
        if ($theme === Gantry::instance()['theme.name']) {
            $gantry = Gantry::instance();

            return $gantry['document']->url('wp-admin/admin.php?page=layout-manager');
        }
        return null;
    }

    public function filter($text)
    {
        return \do_shortcode($text);
    }

    public function query_posts($query)
    {
        return new PostQuery($query);
    }

    public function errorHandlerPaths()
    {
        // Catch errors in Gantry cache, plugin and theme only.
        $paths = ['#[\\\/]wp-content[\\\/](cache|plugins)[\\\/]gantry5[\\\/]#', '#[\\\/]wp-content[\\\/]themes[\\\/]#'];

        // But if we have symlinked git repository, we need to catch errors from there, too.
        if (is_link(GANTRY5_PATH)) {
           $paths = array_merge($paths, ['#[\\\/](assets|engines|platforms)[\\\/](common|wordpress)[\\\/]#', '#[\\\/]src[\\\/](classes|vendor)[\\\/]#', '#[\\\/]themes[\\\/]#']);
        }

        return $paths;
    }

    public function settings()
    {
        return admin_url('options-general.php?page=g5-settings');
    }

    public function update()
    {
        return esc_url(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=') . $this->file, 'upgrade-plugin_' . $this->file));
    }

    public function updates()
    {
        $plugin = get_site_transient('update_plugins');
        $list = [];
        if (!isset($plugin->response[$this->file]) || version_compare(GANTRY5_VERSION, 0) < 0 || !current_user_can('update_plugins')) { return $list; }

        $response = $plugin->response[$this->file];

        $list[] = 'Gantry ' . $response->new_version;

        return $list;
    }

    // getCategories logic for the categories selectize field
    public function getCategories( $args = [] )
    {
        $default = [
            'type'                     => 'post',
            'orderby'                  => 'name',
            'order'                    => 'ASC',
            'hide_empty'               => 0,
            'hierarchical'             => 1,
            'taxonomy'                 => 'category',
            'pad_counts'               => 1
        ];

        $args = wp_parse_args( apply_filters( 'gantry5_form_field_selectize_categories_args', $args ), $default );

        $categories = get_categories( $args );
        $new_categories = [];

        foreach( $categories as $cat ) {
            $new_categories[$cat->cat_ID] = $cat->name;
        }

        return apply_filters( 'gantry5_form_field_selectize_categories', $new_categories );
    }

    public function displayWidgets($key, array $params = [])
    {
        return Widgets::displayPosition($key, $params);
    }

    public function displayWidget($instance = [], array $params = [])
    {
        return Widgets::displayWidget($instance, $params);
    }

    public function listWidgets()
    {
        return Widgets::listWidgets();
    }

    public function displaySystemMessages($params = [])
    {
        /** @var Theme $theme */
        $theme = $this->container['theme'];

        /** @var Messages $messages */
        $messages = $this->container['messages'];

        $context = [
            'messages' => $messages->get(),
            'params' => $params
        ];
        $messages->clean();

        return $theme->render('partials/messages.html.twig', $context);
    }

    public function truncate($text, $length, $html = false)
    {
        if (!$html) {
            $text = strip_tags($text);
        }

        if (!$length) {
            return $text;
        }

        return Utilities::truncate($text, $length, '...', true, $html);
    }
}
