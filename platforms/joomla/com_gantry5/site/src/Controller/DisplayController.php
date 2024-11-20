<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Component\Gantry5\Site\Controller;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default controller for component.
 */
class DisplayController extends BaseController
{
    /**
     * {@inheritDoc}
     *
     * @return  void
     */
    public function display($cachable = false, $urlparams = []): void
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry\Framework\Gantry')) {
            $app->enqueueMessage(
                Text::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', Text::_('COM_GANTRY5_COMPONENT')),
                'warning'
            );

            return;
        }

        $document = $app->getDocument();
        $input    = $app->getInput();
        $menu     = $app->getMenu();
        $menuItem = $menu->getActive();

        $gantry = Gantry::instance();

        // Prevent direct access without menu item.
        if (!$menuItem) {
            if (isset($gantry['errors'])) {
                /** @var \Whoops\Run $errors */
                $errors = $gantry['errors'];
                $errors->unregister();
            }

            throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
        }

        // Handle non-html formats and error page.
        if (
            $input->getCmd('view') === 'error'
            || $input->getInt('g5_not_found')
            || strtolower($input->getCmd('format', 'html')) !== 'html'
        ) {
            if (isset($gantry['errors'])) {
                /** @var \Whoops\Run $errors */
                $errors = $gantry['errors'];
                $errors->unregister();
            }

            throw new \Exception(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        /** @var Registry $params */
        $params = $app->getParams();

        $title = $params->get('page_title');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $document->setTitle($title);

        if ($params->get('menu-meta_description')) {
            $document->setDescription($params->get('menu-meta_description'));
        }

        if ($params->get('menu-meta_keywords')) {
            $document->setMetaData('keywords', $params->get('menu-meta_keywords'));
        }

        if ($params->get('robots')) {
            $document->setMetaData('robots', $params->get('robots'));
        }

        /** @var object $params */
        $data = $params->get('particle') ? \json_decode($params->get('particle'), true) : false;

        if (!$data) {
            return;
        }

        $context = [
            'gantry'    => $gantry,
            'noConfig'  => true,
            'inContent' => true,
            'segment'   => [
                'id'         => 'main-particle',
                'type'       => $data['type'],
                'classes'    => $params->get('pageclass_sfx'),
                'subtype'    => $data['particle'],
                'attributes' => $data['options']['particle'],
            ]
        ];

        echo trim($theme->render('@nucleus/content/particle.html.twig', $context));
    }
}
