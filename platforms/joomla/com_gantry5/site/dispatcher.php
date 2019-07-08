<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Joomla\JoomlaFactory;
use Joomla\CMS\Dispatcher\Dispatcher;
use Joomla\CMS\Language\Text;
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

        $application = $this->getApplication();
        $language = $application->getLanguage();

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry\Framework\Gantry')) {
            $language->load('com_gantry5', JPATH_ADMINISTRATOR)
            || $language->load('com_gantry5', JPATH_ADMINISTRATOR . '/components/com_gantry5');

            $application->enqueueMessage(
                Text::sprintf('COM_GANTRY5_PARTICLE_NOT_INITIALIZED', Text::_('COM_GANTRY5_COMPONENT')),
                'warning'
            );

            return;
        }

        // Prevent direct access without menu item.
        if (!$application->getMenu()->getActive()) {
            throw new NotAllowed(Text::_('JLIB_APPLICATION_ERROR_COMPONENT_NOT_FOUND'), 404);
        }

        // Handle non-html formats and error page.
        if ($this->input->getCmd('format', 'html') !== 'html'
            || $this->input->getCmd('view') === 'error' || $this->input->getInt('g5_not_found')) {
            throw new NotAllowed(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
        }

        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        $params = $application->getParams();

        // Set page title.
        $title = $params->get('page_title');
        if (empty($title)) {
            $title = $application->get('sitename');
        } elseif ($application->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $application->get('sitename'), $title);
        } elseif ($application->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $application->get('sitename'));
        }

        $document = JoomlaFactory::getDocument();
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
        echo trim($theme->render('@nucleus/content/particle.html.twig', $context));
    }
}
