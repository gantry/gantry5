<?php
defined('_JEXEC') or die;

use Gantry\Framework\Gantry;

$app = JFactory::getApplication('site');
$template = $app->getTemplate(true);

try
{
    if (!class_exists('Gantry5\Loader')) {
        throw new RuntimeException('Please install Gantry5 Framework!');
    }

    // Setup Gantry5 Framework or throw exception.
    Gantry5\Loader::setup();

    // Get Gantry instance and return it.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.name']))
    {
        $gantry['theme.path'] = dirname(__DIR__);
        $gantry['theme.name'] = $template->template;
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
    header("HTTP/1.0 500 Internal Server Error");

    $message = sprintf("Failed to load '%s' template: %s", $template->template, $e->getMessage());

    echo <<<html
<html>
    <head>
        <title>500 Internal Server Error</title>
        <style>
        .alert {
            padding: 8px 35px 8px 14px;
            margin-bottom: 18px;
            text-shadow: 0px 1px 0px rgba(255, 255, 255, 0.5);
            background-color: #F2DEDE;
            border-color: #EED3D7;
            color: #B94A48;
            border-radius: 4px;
            font-size: 1.2em;
        }
        </style>
    </head>
    <body>
        <div class="alert">{$message}</div>
    </body>
</html>
html;

    die();
}
