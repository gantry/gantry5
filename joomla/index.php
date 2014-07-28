<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.protostar
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/vendor/autoload.php';

use \Symfony\Component\Yaml\Yaml;

// Getting params from template
$params = JFactory::getApplication()->getTemplate(true)->params;

$app = JFactory::getApplication();
$doc = JFactory::getDocument();
$this->language = $doc->language;
$this->direction = $doc->direction;

// Detecting Active Variables
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->getCfg('sitename');

// Add JavaScript Frameworks
JHtml::_('bootstrap.framework');
// Load optional RTL Bootstrap CSS
JHtml::_('bootstrap.loadCss', false, $this->direction);

$loader_filesystem = new Twig_Loader_Filesystem(__DIR__ . '/twig');
$loader_string = new Twig_Loader_String();
$loader_chain = new Twig_Loader_Chain(array($loader_filesystem, $loader_string));

$params = array(
    'cache' => JPATH_CACHE . '/twig',
    'debug' => true,
    'auto_reload' => true,
    'autoescape' => false
);

$twig = new Twig_Environment($loader_chain, $params);

$loader = $loader_filesystem;

$twig->addFilter('toGrid', new Twig_Filter_Function('toGrid'));
$loader->addPath( __DIR__ . '/nucleus', 'nucleus' );

// Include Gantry specific things to the context.
$context = array();
$context['pageSegments'] = (array) json_decode(file_get_contents(__DIR__ . '/test/nucleus.json'), true);
$context['theme'] = (array) Yaml::parse(file_get_contents(__DIR__ . '/nucleus.yaml'));
$context['page'] = $this;
$context['theme_url'] = JURI::root(false) . '/templates/nucleus';

echo $twig->render('index.html.twig', $context);

/**
 * @param $text
 * @return string
 */
function toGrid($text) {
    static $sizes = array(
        '10'      => 'size-1-10',
        '20'      => 'size-1-5',
        '25'      => 'size-1-4',
        '33.3334' => 'size-1-3',
        '50'      => 'size-1-2',
        '100'     => ''
    );

    return isset($sizes[$text]) ? ' ' . $sizes[$text] : '';
}

$context = array(
    'page' => $this,
);
