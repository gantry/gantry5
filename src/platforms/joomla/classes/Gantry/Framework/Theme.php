<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /**
     * @var bool
     */
    protected $joomla = false;

    /**
     * If parameter is set to true, loads bootstrap. Returns true if bootstrap has been loaded.
     *
     * @param bool|null $enable
     * @return bool
     */
    public function joomla($enable = null)
    {
        if ($enable && !$this->joomla) {
            $this->joomla = true;

            // Workaround for Joomla! not loading bootstrap when it needs it.
            $this->gantry()->load('bootstrap.2');
        }

        return $this->joomla;
    }

    /**
     * @see AbstractTheme::extendTwig()
     *
     * @param \Twig_Environment $twig
     * @param \Twig_LoaderInterface $loader
     * @return \Twig_Environment
     */
    public function extendTwig(\Twig_Environment $twig, \Twig_LoaderInterface $loader = null)
    {
        parent::extendTwig($twig, $loader);

        /** @var \Twig_Extension_Core $core */
        $core = $twig->getExtension('Twig_Extension_Core');

        // Get user timezone and if not set, use Joomla default.
        $timezone = \JFactory::getUser()->getParam('timezone', \JFactory::getConfig()->get('offset', 'UTC'));
        $core->setTimezone(new \DateTimeZone($timezone));

        // Set locale for dates and numbers.
        $core->setDateFormat(\JText::_('DATE_FORMAT_LC2'), \JText::_('GANTRY5_X_DAYS'));
        $core->setNumberFormat(0, \JText::_('DECIMALS_SEPARATOR'), \JText::_('THOUSANDS_SEPARATOR'));

        $filter = new \Twig_SimpleFilter('date', [$this, 'twig_dateFilter'], array('needs_environment' => true));
        $twig->addFilter($filter);

        return $twig;
    }

    /**
     * Converts a date to the given format.
     *
     * <pre>
     *   {{ post.published_at|date("m/d/Y") }}
     * </pre>
     *
     * @param \Twig_Environment                                 $env
     * @param \DateTime|\DateTimeInterface|\DateInterval|string $date     A date
     * @param string|null                                       $format   The target format, null to use the default
     * @param \DateTimeZone|string|null|false                   $timezone The target timezone, null to use the default, false to leave unchanged
     *
     * @return string The formatted date
     */
    public function twig_dateFilter(\Twig_Environment $env, $date, $format = null, $timezone = null)
    {
        if (null === $format) {
            $formats = $env->getExtension('Twig_Extension_Core')->getDateFormat();
            $format = $date instanceof \DateInterval ? $formats[1] : $formats[0];
        }

        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }

        if (!($date instanceof \JDate)) {
            // Create localized JDate object.
            $twig_date = \twig_date_converter($env, $date, $timezone);

            $date = new \JDate($twig_date->getTimestamp());
            $date->setTimezone($twig_date->getTimezone());
        } elseif ($timezone) {
            $date->setTimezone($timezone);
        }

        return $date->format($format, true);
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

        $context = parent::getContext($context);
        $context['site'] = $gantry['site'];
        $context['joomla'] = $gantry['platform'];

        return $context;
    }

    /**
     * @see AbstractTheme::init()
     */
    protected function init()
    {
        parent::init();

        $gantry = Gantry::instance();

        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $lang = \JFactory::getLanguage();

        // FIXME: Do not hardcode this file.
        $lang->load('files_gantry5_nucleus', JPATH_SITE);

        if (\JFactory::getApplication()->isSite()) {
            // Load our custom positions file as frontend requires the strings to be there.
            $filename = $locator("gantry-theme://language/en-GB/en-GB.tpl_{$this->name}_positions.ini");

            if ($filename) {
                $lang->load("tpl_{$this->name}_positions", dirname(dirname(dirname($filename))), 'en-GB');
            }

            // Load template language files, including overrides.
            $paths = $locator->findResources('gantry-theme://language');
            foreach (array_reverse($paths) as $path) {
                $lang->load("tpl_{$this->name}", dirname($path));
            }
        }

        $doc = \JFactory::getDocument();
        if ($doc instanceof \JDocumentHtml) {
            $doc->setHtml5(true);
        }
        $this->language = $doc->language;
        $this->direction = $doc->direction;
        $this->url = \JUri::root(true) . '/templates/' . $this->name;

        \JPluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeInit event.
        $dispatcher = \JEventDispatcher::getInstance();
        $dispatcher->trigger('onGantry5ThemeInit', ['theme' => $this]);
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

        return $locator->mergeResources(['gantry-theme://twig', 'gantry-engine://twig']);
    }

    /**
     * @see AbstractTheme::setTwigLoaderPaths()
     *
     * @param \Twig_LoaderInterface $loader
     * @return \Twig_Loader_Filesystem
     */
    protected function setTwigLoaderPaths(\Twig_LoaderInterface $loader)
    {
        $loader = parent::setTwigLoaderPaths($loader);

        if ($loader) {
            $loader->setPaths($this->getTwigPaths());
        }

        return $loader;
    }
}
