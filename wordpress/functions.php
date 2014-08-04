<?php
defined( 'ABSPATH' ) or die;

try {
    // Initialize Gantry.
    $bootstrap = __DIR__ . '/src/bootstrap.php';
    if ( !is_file( $bootstrap ) ) {
        throw new LogicException( 'Symbolic links missing, please see README.md in your theme!' );
    }
    require_once $bootstrap;

    // Define template.
    class Nucleus extends \Gantry\Theme\Theme {}

    // Create template.
    $theme = new Nucleus(__DIR__);

    // Instantiate Gantry.
    $gantry = \Gantry\Gantry::instance();
    $gantry->initialize($theme);

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
    add_filter( 'template_include', function() use ( $e ) {
        echo 'Theme cannot be loaded. For more information, please login to administration.';
        die();
    });
}
