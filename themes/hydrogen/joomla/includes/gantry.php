<?php
defined('_JEXEC') or die;

use Gantry\Framework\Gantry;

try
{
    // Attempt to locate Gantry Framework if it hasn't already been loaded.
    if (!class_exists('Gantry'))
    {
        $gantryPaths = array(
            __DIR__ . '/../src/bootstrap.php',          // Look if Gantry has been included to the template.
            JPATH_ROOT . '/administrator/components/com_gantryadmin/src/bootstrap.php', // Look for the admin.
        );

        foreach ($gantryPaths as $gantryPath)
        {
            if ($gantryPath && is_file($gantryPath))
            {
                $gantryBootstrap = $gantryPath;
            }
        }

        if (!isset($gantryBootstrap))
        {
            throw new LogicException('Gantry Framework not found!');
        }

        require_once $gantryBootstrap;

        unset($gantryPaths, $gantryPath, $gantryBootstrap);
    }

    // Get Gantry instance and return it.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.id']))
    {
        $app = JFactory::getApplication();
        if ($app->isSite())
        {
            $template = $app->getTemplate(true);

            $gantry['theme.id'] = $template->id;
            $gantry['theme.path'] = dirname(__DIR__);
            $gantry['theme.name'] = $template->template;
            $gantry['theme.params'] = $template->params->toArray();
        }
        else
        {
            throw new RuntimeException('Template was loaded in administration without properly initializing Gantry!');
        }
    }

    // Only a single template can be loaded at any time.
    if (!isset($gantry['theme']))
    {
        include_once __DIR__ . '/theme.php';
    }

    return $gantry;
}
catch (Exception $e)
{
    // Oops, something went wrong!

    // In frontend we want to prevent template from loading.
    JError::raiseError(500, 'Failed to load template: ' . $e->getMessage());
}
