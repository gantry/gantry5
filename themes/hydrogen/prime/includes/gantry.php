<?php
use Gantry\Framework\Gantry;

try
{
    // Get Gantry instance and return it.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.name']))
    {
        $gantry['theme.path'] = dirname(__DIR__);
        $gantry['theme.name'] = $theme;
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
    // In frontend we want to prevent template from loading.
    die('Failed to load template: ' . $e->getMessage());
}
