<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\Dispatcher;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Access\Exception\NotAllowed;

/**
 * Dispatcher class for com_gantry5
 *
 * @since 5.5.0
 */
class Gantry5Dispatcher extends Dispatcher
{
    /**
     * The extension namespace
     *
     * @var    string
     *
     * @since 5.5.0
     */
    protected $namespace = 'Joomla\\Component\\Gantry5';

    /**
     * Dispatch a controller task. Redirecting the user if appropriate.
     *
     * @return  void
     * @throws NotAllowed
     *
     * @since   5.5.0
     */
    public function dispatch()
    {
        // Check component access permission
        $this->checkAccess();

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry\Framework\Gantry')) {
            $this->app->getLanguage()->load('com_gantry5', JPATH_ADMINISTRATOR)
            || $this->app->getLanguage()->load('com_gantry5', JPATH_ADMINISTRATOR . '/components/com_gantry5');

            $this->app->enqueueMessage(
                JText::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', JText::_('COM_GANTRY5_COMPONENT')),
                'warning'
            );

            return;
        }

        // Prevent direct access without menu item.
        if (!$this->app->getMenu()->getActive()) {
            throw new NotAllowed(JText::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
        }

        // Handle non-html formats and error page.
        if ($this->input->getCmd('format', 'html') !== 'html'
            || $this->input->getCmd('view') === 'error' || $this->input->getInt('g5_not_found')) {
            throw new NotAllowed(JText::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $gantry = \Gantry\Framework\Gantry::instance();

        /** @var Gantry\Framework\Theme $theme */
        $theme = $gantry['theme'];

        $params = $this->app->getParams();

        // Set page title.
        $title = $params->get('page_title');
        if (empty($title)) {
            $title = $this->app->get('sitename');
        } elseif ($this->app->get('sitename_pagetitles', 0) == 1) {
            $title = JText::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
        } elseif ($this->app->get('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
        }

        $document = JFactory::getDocument();
        $document->setTitle($title);

        // Set description.
        if ($params->get('menu-meta_description')) {
            $document->setDescription($params->get('menu-meta_description'));
        }

        // Set Keywords.
        if ($params->get('menu-meta_keywords')) {
            $document->setMetadata('keywords', $params->get('menu-meta_keywords'));
        }

        // Set robots.
        if ($params->get('robots')) {
            $document->setMetadata('robots', $params->get('robots'));
        }

        $data = json_decode($params->get('particle'), true);
        if (!$data) {
            // No component output.
            return;
        }

        $context = [
            'gantry' => $gantry,
            'noConfig' => true,
            'inContent' => true,
            'segment' => [
                'id' => 'main-particle',
                'type' => $data['type'],
                'classes' => $params->get('pageclass_sfx'),
                'subtype' => $data['particle'],
                'attributes' => $data['options']['particle'],
            ]
        ];

        // Render the particle.
        echo trim($theme->render("@nucleus/content/particle.html.twig", $context));
    }
}
