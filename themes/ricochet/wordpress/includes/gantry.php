<?php
defined( 'ABSPATH' ) or die;

use Gantry\Framework\Gantry;

try {
    // Attempt to locate Gantry Framework if it hasn't already been loaded.
    if ( !class_exists( 'Gantry' ) ) {
        $paths = array(
            __DIR__ . '/../src/bootstrap.php',                      // Look if Gantry has been included to the template.
            WP_CONTENT_DIR . '/themes/gantry/src/bootstrap.php'     // Finally look from the default gantry template.
        );
        foreach ( $paths as $path ) {
            if ( $path && is_file( $path ) ) {
                $bootstrap = $path;
            }
        }

        if ( !$bootstrap ) {
            throw new LogicException( 'Gantry Framework not found!' );
        }

        require_once $bootstrap;
    }

    // Get Gantry instance.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry[ 'theme.id' ]))
    {
        $gantry[ 'theme.id' ] = 0;
        $gantry[ 'theme.path' ] = get_stylesheet_directory();
        $gantry[ 'theme.name' ] = get_option( 'template' );
        $gantry[ 'theme.params' ] = [];
    }

    // Only a single template can be loaded at any time.
    if (!isset($gantry[ 'theme' ]))
    {
        include_once __DIR__ . '/theme.php';
    }

} catch ( Exception $e ) {
    // Oops, something went wrong!
    if ( is_admin() ) {
        // In admin display an useful error.
        add_action( 'admin_notices', function() use ( $e ) {
            echo '<div class="error"><p>Failed to load theme: ' . $e->getMessage(). '</p></div>';
        } );
        return;
    }

    // In frontend we want to prevent template from loading.
    if (class_exists( '\Tracy\Debugger' ) && \Tracy\Debugger::isEnabled() && !\Tracy\Debugger::$productionMode ) {
        // We have Tracy enabled; will display and/or log error with it.
        throw $e;
    }

    add_filter( 'template_include', function() use ( $e ) {
        echo 'Theme cannot be used. For more information, please login to administration.';
        die();
    });

    return;
}

// Hook into administration.
if ( is_admin() ) {
    if ( file_exists(__DIR__ . '/admin/init.php') ) {
        define( 'GANTRYADMIN_PATH', __DIR__ . '/admin' );
    }

    add_action(
        'init',
        function () {
            if ( defined('GANTRYADMIN_PATH') ) {
                require_once GANTRYADMIN_PATH . '/init.php';
            }
        }
    );
}

return $gantry;
