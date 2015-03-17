<?php
defined('_JEXEC') or die;

use Gantry\Framework\Gantry;

try
{
    if (!class_exists('Gantry5\Loader')) {
        throw new LogicException('System - Gantry Framework plugin / Gantry Library missing.');
    }

    // Setup Gantry5 Framework or throw exception.
    Gantry5\Loader::setup();

    // Get Gantry instance and return it.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.id']))
    {
        $template = $app->getTemplate(true);

        $gantry['theme.id'] = $template->id;
        $gantry['theme.path'] = dirname(__DIR__);
        $gantry['theme.name'] = $template->template;
        $gantry['theme.params'] = $template->params->toArray();
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
    JError::raiseError(500, 'Failed to load template: ' . $e->getMessage());
}
