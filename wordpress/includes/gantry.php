<?php
defined( 'ABSPATH' ) or die;

use Gantry\Framework\Gantry;

try {
    // Load Gantry Framework.
    $bootstrap = __DIR__ . '/../src/bootstrap.php';
    if ( !is_file( $bootstrap ) ) {
        throw new LogicException( 'Symbolic links missing, please see README.md in your theme!' );
    }
    require_once $bootstrap;

    // Get Gantry instance and return it.
    return Gantry::instance();

} catch ( Exception $e ) {
    // Oops, something went wrong!

    if ( is_admin() ) {
        // In admin display an useful error.
        add_action( 'admin_notices', function() use ( $e ) {
            echo '<div class="error"><p>Theme error: ' . $e->getMessage(). '</p></div>';
        } );
        return;
    }

    // In frontend we want to prevent template from loading.
    if ( !class_exists( '\Tracy\Debugger' ) ) {
        add_filter( 'template_include', function() use ( $e ) {
            echo 'Theme cannot be loaded. For more information, please login to administration.';
            die();
        });
    }

    // We have Tracy enabled; will display and/or log error with it.
    throw $e;
}
