<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use Gantry\Framework\Base\Platform as BasePlatform;
use Pimple\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform extends BasePlatform {
    protected $name = 'wordpress';

    public function __construct(Container $container) {
        $this->content_dir = Folder::getRelativePath(WP_CONTENT_DIR);
        $this->gantry_dir = Folder::getRelativePath(GANTRY5_PATH);

        parent::__construct($container);
    }

    public function getCachePath() {
        return WP_CONTENT_DIR . '/cache/gantry5';
    }

    public function getThemesPaths() {
        return ['' => Folder::getRelativePath(get_theme_root())];
    }

    public function getMediaPaths() {
        return ['' => [
            'gantry-theme://images',
            $this->content_dir . '/uploads',
            $this->gantry_dir
            ]
        ];
    }

    public function getEnginesPaths() {
        if (is_link(GANTRY5_PATH . '/engines')) {
            // Development environment.
            return ['' => [$this->gantry_dir . "/engines/{$this->name}", $this->gantry_dir . '/engines/common']];
        }

        return ['' => [$this->gantry_dir . '/engines']];
    }

    public function getAssetsPaths() {
        if (is_link(GANTRY5_PATH . '/assets')) {
            // Development environment.
            return ['' => ['gantry-theme://', $this->gantry_dir . "/assets/{$this->name}", $this->gantry_dir . '/assets/common']];
        }

        return ['' => ['gantry-theme://', $this->gantry_dir . '/assets']];
    }

    public function finalize() {
        Document::registerAssets();
    }

    public function errorHandlerPaths() {
        // Catch errors in Gantry cache, plugin and theme only.
        $paths = ['#[\\\/]wp-content[\\\/](cache|plugins)[\\\/]gantry5[\\\/]#', '#[\\\/]wp-content[\\\/]themes[\\\/]#'];

        // But if we have symlinked git repository, we need to catch errors from there, too.
        if (is_link(GANTRY5_PATH)) {
           $paths = array_merge($paths, ['#[\\\/](assets|engines|platforms)[\\\/](common|wordpress)[\\\/]#', '#[\\\/]src[\\\/](classes|vendor)[\\\/]#', '#[\\\/]themes[\\\/]#']);
        }

        return $paths;
    }

    // getCategories logic for the categories selectize field
    public function getCategories( $args = [] ) {
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

    public function displayWidget($instance = [], array $args = [])
    {
        if (is_string($instance)) {
            $instance = json_decode($instance, true);
        }
        if (!isset($instance['type']) || $instance['type'] !== 'widget' || !isset($instance['widget']) || !isset($instance['options'])) {
            return null;
        }

        $options = $instance['options'];
        if (empty($options['enabled'])) {
            return null;
        }

        $widgetClass = $this->getWidgetClass($instance['widget']);

        ob_start();
        \the_widget($widgetClass, $options['widget'], $args);
        return ob_get_clean();
    }

    protected function getWidgetClass($id)
    {
        $widgets = $this->listWidgets();
        if (!isset($widgets[$id])) {
            return null;
        }
        return $widgets[$id]['class'];
    }

    public function listWidgets()
    {
        $widgets = $GLOBALS['wp_widget_factory']->widgets;

        $list = [];
        foreach ($widgets as $key => $widget) {
            $info = ['id' => $widget->id_base, 'title' => $widget->name, 'description' => $widget->widget_options['description'], 'class' => $key, 'widget' => $widget];
            $list[$widget->id_base] = $info;
        }

        uasort($list, function ($a, $b) { return strcmp($a['title'], $b['title']); });

        return $list;
    }
}
