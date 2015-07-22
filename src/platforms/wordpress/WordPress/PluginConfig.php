<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\WordPress;

class PluginConfig {
    function __construct() {
        global $plugin, $network_plugin;

        register_activation_hook( $plugin, [ &$this, 'g5_plugin_defaults' ] );

        add_action( 'admin_init', [ &$this, 'register_admin_settings' ] );

        add_plugins_page( 'Gantry 5 Settings', 'Gantry 5 Settings', 'manage_options', 'g5-settings', [ &$this, 'g5_plugin_settings' ] );
    }

    function g5_plugin_defaults() {
        $defaults = [
            'production' => '1',
            'debug' => '0',
        ];

        add_option( 'gantry5_plugin', $defaults );
    }

    function register_admin_settings() {
        register_setting( 'gantry5_plugin_options', 'gantry5_plugin' );
    }

    function g5_plugin_settings() {
        $option = get_option( 'gantry5_plugin_options' );

        if( isset( $_GET[ 'settings-updated' ] ) && $_GET[ 'settings-updated' ] == 'true' ) {
            echo '<div id="message" class="updated fade"><p>Gantry 5 plugin settings saved.</p></div>';
        }

        ?>

        <div id="g5-options-main">
            <form method="post" action="options.php">
                <?php settings_fields( 'gantry5_plugin' ); ?>

                <table class="widefat fixed">
                    <tfoot>
                        <tr>
                            <th colspan="2">
                                <input type="submit" class="button button-primary rb-submit" value="<?php _e('Save Changes', 'gantry5'); ?>" />
                            </th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr>
                            <td>
                                <h3 class="available-options"><?php _e( 'Available Options', 'gantry5' ); ?></h3>
                                <div class="param-row alternate first">
                                    <div class="label"><?php _e( 'Production Mode', 'gantry5' ); ?></div>
                                    <div class="option">
                                        <input id="production1" type="radio" <?php checked( $option[ 'production' ], '1' ); ?> value="1" name="gantry5_plugin[production]"/>
                                        <label for="production1"><?php _e('Enable', 'gantry5'); ?></label>&nbsp;&nbsp;
                                        <input id="production2" class="second" type="radio" <?php checked( $option['production'], '0' ); ?> value="0" name="gantry5_plugin[production]"/>
                                        <label for="production2"><?php _e('Disable', 'gantry5'); ?></label>
                                    </div>
                                </div>
                                <div class="param-row last">
                                    <div class="label"><?php _e( 'Debug Mode', 'gantry5' ); ?></div>
                                    <div class="option">
                                        <input id="debug1" type="radio" <?php checked( $option[ 'debug' ], '1' ); ?> value="1" name="gantry5_plugin[debug]"/>
                                        <label for="debug1"><?php _e('Enable', 'gantry5'); ?></label>&nbsp;&nbsp;
                                        <input id="debug2" class="second" type="radio" <?php checked( $option['debug'], '0' ); ?> value="0" name="gantry5_plugin[debug]"/>
                                        <label for="debug2"><?php _e('Disable', 'gantry5'); ?></label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>

        <?php
    }
}
