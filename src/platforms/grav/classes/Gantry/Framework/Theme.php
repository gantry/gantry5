<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use Grav\Common\Grav;
use Grav\Common\Twig\Twig;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /**
     * Return renderer.
     *
     * @return \Twig_Environment
     */
    public function renderer()
    {
        if (!$this->renderer) {
            $gantry = static::gantry();
            $grav = Grav::instance();

            /** @var Twig $gravTwig */
            $gravTwig = $grav['twig'];

            $twig = $gravTwig->twig();
            $loader = $gravTwig->loader();

            /** @var Config $global */
            $global = $gantry['global'];

            $debug = $gantry->debug();
            $production = (bool) $global->get('production', 1);

            if ($debug && !$twig->isDebug()) {
                $twig->enableDebug();
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            if ($production) {
                $twig->disableAutoReload();
            } else {
                $twig->enableAutoReload();
            }

            // Force html escaping strategy.
            $twig->getExtension('escaper')->setDefaultStrategy('html');

            $this->setTwigLoaderPaths($loader);

            $this->renderer = $this->extendTwig($twig, $loader);
        }

        return $this->renderer;
    }

    /**
     * Get list of twig paths.
     *
     * @return array
     */
    public static function getTwigPaths()
    {
        /** @var UniformResourceLocator $locator */
        $locator = static::gantry()['locator'];

        return $locator->mergeResources(['gantry-theme://templates', 'gantry-engine://templates']);
    }

    /**
     * @see AbstractTheme::getContext()
     *
     * @param array $context
     * @return array
     */
    public function getContext(array $context)
    {
        $gantry = static::gantry();
        $grav = Grav::instance();
        $page = $grav['page'];

        $context = parent::getContext($context);
        $context = array_replace($context, $grav['twig']->twig_vars);
        $context['site'] = $gantry['site'];

        // Emulate site context.
        if (!isset($context['theme'])) {
            $context['theme'] = $grav['config']->get('theme');
        }
        if (!isset($context['pages'])) {
            $context['pages'] = $grav['pages']->root();
        }
        if (!isset($context['page'])) {
            $context['page'] = $page;
        }
        if (!isset($context['header'])) {
            $context['header'] = $page->header();
        }
        if (!isset($context['media'])) {
            $context['media'] = $page->media();
        }
        if (!isset($context['content'])) {
            $context['content'] = $page->content();
        }

        return $context;
    }
}
