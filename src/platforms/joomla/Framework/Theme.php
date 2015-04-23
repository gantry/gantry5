<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Framework\Base\Theme as BaseTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Theme extends BaseTheme
{
    protected $joomla;

    public function __construct($path, $name = '')
    {
        parent::__construct($path, $name);

        $this->url = \JUri::root(true) . '/templates/' . $this->name;
    }

    public function render($file, array $context = array())
    {
        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $loader = new \Twig_Loader_Filesystem($locator->findResources('gantry-engine://twig'));

        $params = array(
            'cache' => $locator('gantry-cache://twig', true, true),
            'debug' => true,
            'auto_reload' => true,
            'autoescape' => false
        );

        $twig = new \Twig_Environment($loader, $params);

        $this->add_to_twig($twig);

        // Include Gantry specific things to the context.
        $context = $this->add_to_context($context);

        $doc = \JFactory::getDocument();
        $this->language = $doc->language;
        $this->direction = $doc->direction;

        return $twig->render($file, $context);
    }

    public function debug()
    {
        return JDEBUG;
    }

    public function joomla($enable = null)
    {
        if ($enable !== null) {
            if ($enable) {
                // Workaround for Joomla! not loading bootstrap when it needs it.
                \JHtml::_('bootstrap.framework');
            }
            $this->joomla = (bool) $enable;
        }

        return (bool) $this->joomla;
    }
}
