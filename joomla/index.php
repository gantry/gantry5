<?php

defined('_JEXEC') or die;

try
{
    // Initialize Gantry.
    $bootstrap = __DIR__ . '/src/bootstrap.php';

    if (!is_file($bootstrap))
    {
        throw new LogicException('Symbolic links missing, please see README.md in your theme!');
    }

    require_once $bootstrap;

    // Define template.
    class Nucleus extends \Gantry\Theme\Theme {}

    // Create template.
    $theme = new Nucleus(__DIR__, $this->template);

    // Instantiate Gantry.
    $gantry = \Gantry\Gantry::instance();
    $gantry->initialize($theme);
}
catch (Exception $e)
{
    // Oops, something went wrong!
    // In frontend we want to prevent template from loading.
    if (!class_exists('\Tracy\Debugger'))
    {
        JError::raiseError(500, 'Template cannot be loaded: ' . $e->getMessage());
    };

    // We have Tracy enabled; will display and/or log error with it.
    throw $e;
}

echo $theme->render('index.html.twig');
