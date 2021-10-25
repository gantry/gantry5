<?php
defined('ABSPATH') or die;

use Gantry\Admin\Router;
use Gantry\Framework\Document;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme;
use Gantry\WordPress\NavMenuEditWalker;
use Gantry5\Loader;

add_action('admin_init', 'gantry5_admin_start_buffer', -10000);
add_action('admin_enqueue_scripts', 'gantry5_admin_scripts');
add_action('wp_ajax_gantry5', 'gantry5_layout_manager');
add_filter('upgrader_package_options', 'gantry5_upgrader_package_options', 10000);
add_filter('upgrader_source_selection', 'gantry5_upgrader_source_selection', 0, 4);
add_action('upgrader_post_install', 'gantry5_upgrader_post_install', 10, 3);

// Custom menu type:
add_action('admin_head', 'gantry5_add_menu_item_types', 99);
add_filter('wp_setup_nav_menu_item', 'gantry5_customize_menu_item_label');
add_filter('wp_edit_nav_menu_walker', 'gantry5_wp_edit_nav_menu_walker');
add_filter('pre_wp_unique_post_slug', 'gantry5_wp_unique_post_slug', 0, 6);

// Check if Timber is active before displaying sidebar button
if (class_exists( 'Timber')) {
    // Load Gantry 5 icon styling for the admin sidebar
    add_action(
        'admin_enqueue_scripts',
        static function() {
            if(is_admin()) {
                wp_enqueue_style( 'wordpress-admin-icon', Document::url('gantry-assets://css/wordpress-admin-icon.css'));
            }
        }
    );

    // Adjust menu to contain Gantry stuff.
    add_action(
        'admin_menu',
        static function() {
            $gantry = Gantry::instance();

            /** @var Theme $theme */
            $theme = $gantry['theme'];
            $name = $theme->details()['details.name'];
            remove_submenu_page('themes.php', 'theme-editor.php');
            add_menu_page("{$name} Theme", "{$name} Theme", 'manage_options', 'layout-manager', 'gantry5_layout_manager');
        },
        100
    );
}

function gantry5_admin_start_buffer()
{
    ob_start();
    ob_implicit_flush(false);
}

function gantry5_init()
{
    $gantry = Gantry::instance();
    if (!isset($gantry['router'])) {
        $gantry['router'] = $router = new Router($gantry);
        $router->boot()->load();
    }

    return $gantry;
}

function gantry5_add_menu_item_type_particle()
{
    global $_nav_menu_placeholder, $nav_menu_selected_id;

    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

    $gantry = gantry5_init();

    // Get full list of particles.
    $particles = $gantry['particles']->all();
    ?>
    <div class="posttypediv" id="custom-item-types">
        <div id="tabs-panel-custom-item-types" class="tabs-panel tabs-panel-active">
            <ul id="custom-item-types-checklist" class="categorychecklist form-no-clear">
                <?php foreach ($particles as $name => $particle): ?>
                <?php if ($name !== 'widget' && $particle['type'] !== 'particle') continue; ?>
                <li>
                    <label class="menu-item-title">
                        <input type="radio" class="menu-item-checkbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="-1">
                        <?php echo $particle['name']; ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php echo $particle['name']; ?>">
                    <input type="hidden" class="menu-item-attr-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-attr-title]" value="gantry-particle-<?php echo $name; ?>"/>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <input type="hidden" value="custom" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" />

        <p class="button-controls wp-clearfix">
            <span class="add-to-menu">
                <input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'gantry5' ); ?>" name="add-custom-menu-item" id="submit-custom-item-types" />
                <span class="spinner"></span>
            </span>
        </p>

    </div>
    <?php
}

function gantry5_customize_menu_item_label($menu_item)
{
    if ('custom' !== $menu_item->type || strpos($menu_item->attr_title, 'gantry-particle-') !== 0) {
        return $menu_item;
    }

    $gantry = gantry5_init();

    // Get full list of particles.
    $particles = $gantry['particles']->all();

    $id = substr($menu_item->attr_title, strlen('gantry-particle-'));

    if (isset($particles[$id])) {
        $menu_item->type_label = $particles[$id]['name'] . ' ' . __('Particle', 'gantry5');
    } else {
        $menu_item->type_label = __('Unknown Particle', 'gantry5');
    }

    return $menu_item;
}

function gantry5_wp_edit_nav_menu_walker()
{
    gantry5_init();

    return NavMenuEditWalker::class;
}

function gantry5_wp_unique_post_slug($override_slug, $slug, $post_ID, $post_status, $post_type, $post_parent)
{
    global $wpdb;
    if ($post_type !== 'nav_menu_item') {
        return null;
    }
    if (strpos($slug, '__particle-') === 0) {
        return $slug;
    }

    $sql = "SELECT * FROM $wpdb->posts WHERE post_type = %s AND ID = %d LIMIT 1";
    $post = $wpdb->get_row($wpdb->prepare($sql, $post_type, $post_ID));
    if (!isset($post->content) || strpos($post->post_excerpt, 'gantry-particle-') !== 0) {
        return null;
    }

    if (strpos($post->content, '__particle-') === 0) {
        return $post->content;
    }

    return "__particle-{$post_ID}";
}


function gantry5_add_menu_item_types()
{
    add_meta_box('gantry_particles', __('Particles', 'gantry5'), 'gantry5_add_menu_item_type_particle', 'nav-menus', 'side', 'low');
}

function gantry5_admin_scripts()
{
    if (isset($_GET['page']) && $_GET['page'] === 'layout-manager') {
        gantry5_layout_manager();
    }
}

function gantry5_layout_manager()
{
    static $output = null;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    add_filter('admin_body_class', static function() {
        return 'gantry5 gantry5-wordpress';
    });

    if ($output) {
        echo $output;
        return;
    }

    // Detect Gantry Framework or fail gracefully.
    if (!class_exists(Loader::class)) {
        wp_die(__('Gantry 5 Framework not found.'));
    }

    // Initialize administrator or fail gracefully.
    try {
        Loader::setup();

        $gantry = Gantry::instance();
        $router = new Router($gantry);
        $gantry['router'] = $router;

        // Dispatch to the controller.
        $output = $router->dispatch();
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * SimpleXmlElement is a weird class that acts like a boolean, we are going to take advantage from that.
 */
class Gantry5Truthy extends SimpleXMLElement {}

function gantry5_upgrader_package_options($options)
{
    if (isset($options['hook_extra']['type']) && !$options['clear_destination']) {
        if ($options['hook_extra']['type'] === 'theme' && $options['abort_if_destination_exists']) {
            // Prepare for manual theme upgrade.
            $options['abort_if_destination_exists'] = new Gantry5Truthy('<bool><true></true></bool>');
            $options['hook_extra']['gantry5_abort'] = $options['abort_if_destination_exists'];
        } elseif ($options['hook_extra']['type'] === 'plugin' && strpos(basename($options['package']), 'gantry5') !== false) {
            // Allow Gantry plugin to be manually upgraded / downgraded.
            $options['clear_destination'] = true;
        }
    }

    return $options;
}

function gantry5_upgrader_source_selection($source, $remote_source, $upgrader, $options = [])
{
    // Allow upgrading Gantry themes from uploader.
    if (isset($options['gantry5_abort']) && file_exists("{$source}/gantry/theme.yaml")) {
        $upgrader->skin->feedback('Gantry 5 theme detected.');
        unset($options['gantry5_abort']->true);
    }

    return $source;
}

function gantry5_upgrader_post_install($success, $options, $result)
{
    if ($success) {
        $theme = isset($options['gantry5_abort']) && !$options['gantry5_abort'];
        $plugin = (isset($options['plugin']) && $options['plugin'] === 'gantry5/gantry5.php')
            || (isset($options['type']) && $options['type'] === 'plugin' && basename($result['destination']) === 'gantry5');

        // Clear gantry cache after plugin / Gantry theme installs.
        if ($theme || $plugin) {
            global $wp_filesystem;

            $gantry = Gantry::instance();

            /** @var Platform $platform */
            $platform = $gantry['platform'];

            $path = $platform->getCachePath();
            if ($wp_filesystem->is_dir($path)) {
                $wp_filesystem->rmdir($path, true);
            }

            // Make sure that PHP has the latest data of the files.
            clearstatcache();

            // Remove all compiled files from opcode cache.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        }
    }
}
