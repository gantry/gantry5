<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

class JFormFieldParticle extends JFormField
{
    protected $type = 'Particle';
    protected $container;

    protected function getInput()
    {
        $app = JFactory::getApplication();

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry5\Loader')) {
            $app->enqueueMessage(
                JText::sprintf('MOD_GANTRY5_PLUGIN_MISSING', JText::_('MOD_GANTRY5_PARTICLE')),
                'error'
            );
            return '';
        }

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', JPATH_ADMINISTRATOR . '/components/com_gantry5');
        }

        // Initialize administrator or fail gracefully.
        try {
            Gantry5\Loader::setup();

            $lang = JFactory::getLanguage();
            $lang->load('com_gantry5', JPATH_ADMINISTRATOR) || $lang->load('com_gantry5', GANTRYADMIN_PATH);

            $this->container = Gantry\Framework\Gantry::instance();
            $this->container['router'] = function ($c) {
                return new \Gantry\Admin\Router($c);
            };

        } catch (Exception $e) {
            $app->enqueueMessage(
                JText::sprintf($e->getMessage()),
                'error'
            );
            return '';
        }

        // TODO: Use better style detection.
        $style = \Gantry\Joomla\StyleHelper::getDefaultStyle();

        if (!$style->template) {
            $app->enqueueMessage(
                JText::_("GANTRY5_PARTICLE_FIELD_NO_DEFAULT_STYLE"),
                'warning'
            );
        } elseif (!file_exists(JPATH_SITE . "/templates/{$style->template}/gantry/theme.yaml")) {
            $app->enqueueMessage(
                JText::sprintf("GANTRY5_PARTICLE_FIELD_NO_GANTRY5_STYLE", $style->title),
                'warning'
            );
        }

        $this->container['router']->setTheme($style->template, null)->load();

        $field = [
            'default' => true,
            'scope' => '',
            'name' => $this->name,
            'field' => [
                'type' => 'gantry.particle',
                'label' => 'Particle',
                'class' => 'input-small',
                'picker_label' => 'Pick a Particle',
                'overridable' => false
            ],
            'value' => json_decode($this->value, true)
        ];

        $params = [
            'content' => $this->container['admin.theme']->render('@gantry-admin/forms/fields/gantry/particle.html.twig', $field)
        ];

        return $this->container['admin.theme']->render('@gantry-admin/partials/layout.html.twig', $params);
    }
}
