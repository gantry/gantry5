<?php
defined('ABSPATH') or die;

add_action( 'admin_init', 'gantry5_register_admin_settings' );
add_action( 'admin_menu', 'gantry5_manage_settings' );
add_action( 'network_admin_menu', 'gantry5_manage_settings' );
add_filter( 'plugin_action_links', 'gantry5_modify_plugin_action_links', 10, 2 );
add_filter( 'network_admin_plugin_action_links', 'gantry5_modify_plugin_action_links', 10, 2 );

function gantry5_register_admin_settings() {
    register_setting( 'gantry5_plugin_options', 'gantry5_plugin' );
}

function gantry5_manage_settings()
{
    add_submenu_page( null, 'Gantry 5 Settings', 'Gantry 5 Settings', 'manage_options', 'g5-settings', 'gantry5_plugin_settings' );
}

function gantry5_modify_plugin_action_links( $links, $file ) {
    // Return normal links if not Gantry 5
    if ( plugin_basename( GANTRY5_PATH . '/gantry5.php' ) != $file ) {
        return $links;
    }

    // Add a few links to the existing links array
    return array_merge( $links, array(
        'settings' => '<a href="' . esc_url( add_query_arg( [ 'page' => 'g5-settings' ] ) ) . '">' . esc_html__( 'Settings', 'gantry5' ) . '</a>'
    ) );

}

function gantry5_plugin_settings() {
    $option = get_option( 'gantry5_plugin' );

    if( isset( $_GET[ 'settings-updated' ] ) && $_GET[ 'settings-updated' ] == 'true' ) {
        echo '<div id="message" class="updated fade"><p>' . __( 'Gantry 5 plugin settings saved.', 'gantry5' ) . '</p></div>';
    }

    ?>

    <div id="g5-options-main">
        <div class="wrap">
            <form method="post" action="<?php echo admin_url('options.php'); ?>">
                <?php settings_fields( 'gantry5_plugin_options' ); ?>

                <h1 class="available-options"><?php _e( 'Gantry 5 Settings', 'gantry5' ); ?></h1>

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="production1"><?php _e( 'Production Mode', 'gantry5' ); ?></label>
                            </th>
                            <td>
                                <input id="production1" type="radio" <?php checked( $option[ 'production' ], '1' ); ?> value="1" name="gantry5_plugin[production]"/>
                                <label for="production1"><?php _e( 'Enable', 'gantry5' ); ?></label>&nbsp;&nbsp;
                                <input id="production2" class="second" type="radio" <?php checked( $option['production'], '0' ); ?> value="0" name="gantry5_plugin[production]"/>
                                <label for="production2"><?php _e( 'Disable', 'gantry5' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="debug1"><?php _e( 'Debug Mode', 'gantry5' ); ?></label>
                            </th>
                            <td>
                                <input id="debug1" type="radio" <?php checked( $option[ 'debug' ], '1' ); ?> value="1" name="gantry5_plugin[debug]"/>
                                <label for="debug1"><?php _e( 'Enable', 'gantry5' ); ?></label>&nbsp;&nbsp;
                                <input id="debug2" class="second" type="radio" <?php checked( $option['debug'], '0' ); ?> value="0" name="gantry5_plugin[debug]"/>
                                <label for="debug2"><?php _e( 'Disable', 'gantry5' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="offline1"><?php _e( 'Offline Mode', 'gantry5' ); ?></label>
                            </th>
                            <td>
                                <input id="offline1" type="radio" <?php checked( $option[ 'offline' ], '1' ); ?> value="1" name="gantry5_plugin[offline]"/>
                                <label for="offline1"><?php _e( 'Enable', 'gantry5' ); ?></label>&nbsp;&nbsp;
                                <input id="offline2" class="second" type="radio" <?php checked( $option['offline'], '0' ); ?> value="0" name="gantry5_plugin[offline]"/>
                                <label for="offline2"><?php _e( 'Disable', 'gantry5' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="offline_message"><?php _e( 'Offline Message', 'gantry5' ); ?></label>
                            </th>
                            <td>
                                <input id="offline_message" type="text" value="<?php echo $option[ 'offline_message' ]; ?>" class="regular-text" name="gantry5_plugin[offline_message]" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cache_path"><?php _e( 'Cache Path', 'gantry5' ); ?></label>
                            </th>
                            <td>
                                <input id="cache_path" type="text" value="<?php echo $option[ 'cache_path' ]; ?>" placeholder="<?php echo WP_CONTENT_DIR; ?>/cache/gantry5" class="regular-text" name="gantry5_plugin[cache_path]" />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' ); ?>" />
                </p>
            </form>
        </div>
    </div>

<?php
}
