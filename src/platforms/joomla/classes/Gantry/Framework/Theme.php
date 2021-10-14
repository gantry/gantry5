<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Theme\AbstractTheme;
use Gantry\Component\Theme\ThemeTrait;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version as JVersion;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;

/**
 * Class Theme
 * @package Gantry\Framework
 */
class Theme extends AbstractTheme
{
    use ThemeTrait;

    /** @var bool */
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

            // Workaround for Joomla! 3.x not loading bootstrap when it needs it.
            if (version_compare(JVERSION, '4', '<')) {
                static::gantry()->load('bootstrap');
            }
        }

        return $this->joomla;
    }

    /**
     * @see AbstractTheme::extendTwig()
     *
     * @param Environment $twig
     * @param LoaderInterface $loader
     * @return Environment
     */
    public function extendTwig(Environment $twig, LoaderInterface $loader = null)
    {
        parent::extendTwig($twig, $loader);

        /** @var CoreExtension $core */
        $core = $twig->getExtension(CoreExtension::class);

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $user = $application->getIdentity();

        // Get user timezone and if not set, use Joomla default.
        $timezone = Factory::getConfig()->get('offset', 'UTC');
        if ($user) {
            $timezone = $user->getParam('timezone', $timezone);
        }
        $core->setTimezone(new \DateTimeZone($timezone));

        // Set locale for dates and numbers.
        $core->setDateFormat(Text::_('DATE_FORMAT_LC2'), Text::_('GANTRY5_X_DAYS'));
        $core->setNumberFormat(0, Text::_('DECIMALS_SEPARATOR'), Text::_('THOUSANDS_SEPARATOR'));

        $filter = new TwigFilter('date', [$this, 'twig_dateFilter'], ['needs_environment' => true]);
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
     * @param Environment                                       $env
     * @param \DateTime|\DateTimeInterface|\DateInterval|string $date     A date
     * @param string|null                                       $format   The target format, null to use the default
     * @param \DateTimeZone|string|null|false                   $timezone The target timezone, null to use the default, false to leave unchanged
     *
     * @return string The formatted date
     */
    public function twig_dateFilter(Environment $env, $date, $format = null, $timezone = null)
    {
        if (null === $format) {
            $formats = $env->getExtension(CoreExtension::class)->getDateFormat();
            $format = $date instanceof \DateInterval ? $formats[1] : $formats[0];
        }

        if ($date instanceof \DateInterval) {
            return $date->format($format);
        }

        if (!($date instanceof Date)) {
            // Create localized Date object.
            $twig_date = \twig_date_converter($env, $date, $timezone);

            $date = new Date($twig_date->getTimestamp());
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

        /** @var CMSApplication $application */
        $application = Factory::getApplication();
        $language = $application->getLanguage();

        // FIXME: Do not hardcode this file.
        $language->load('files_gantry5_nucleus', JPATH_SITE);

        if ($application->isClient('site')) {
            // Load our custom positions file as frontend requires the strings to be there.
            $filename = $locator("gantry-theme://language/en-GB/en-GB.tpl_{$this->name}_positions.ini");

            if ($filename) {
                $language->load("tpl_{$this->name}_positions", \dirname(\dirname(\dirname($filename))), 'en-GB');
            }

            // Load template language files, including overrides.
            $paths = $locator->findResources('gantry-theme://language');
            foreach (array_reverse($paths) as $path) {
                $language->load("tpl_{$this->name}", \dirname($path));
            }
        }

        $this->language = 'en-gb';
        $this->direction = 'ltr';
        $this->url = Uri::root(true) . '/templates/' . $this->name;

        PluginHelper::importPlugin('gantry5');

        // Trigger the onGantryThemeInit event.
        $application->triggerEvent('onGantry5ThemeInit', ['theme' => $this]);
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
     * @param LoaderInterface $loader
     * @return FilesystemLoader
     */
    protected function setTwigLoaderPaths(LoaderInterface $loader)
    {
        $loader = parent::setTwigLoaderPaths($loader);

        if ($loader) {
            $loader->setPaths(self::getTwigPaths());
        }

        return $loader;
    }
}
