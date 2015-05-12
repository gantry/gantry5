<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

class JFormFieldParticle extends JFormField
{
    protected $type = 'Particle';
    protected $container;

    public function renderField($options = array())
    {
        return $this->getInput();
    }

    protected function getLabel()
    {
        return '';
    }

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

        // Dispatch to the controller.
        // FIXME:
        $this->container['router']->setStyle(128)->load();

        return $this->selectParticle();
    }

    protected function selectParticle()
    {
        $groups = [
            'Particles' => ['particle' => []],
        ];

        $particles = [
            'position'    => [],
            'spacer'      => [],
            'pagecontent' => [],
            'particle' => [],
        ];

        $particles = array_replace($particles, $this->getParticles());
        unset($particles['atom'], $particles['position']);

        foreach ($particles as &$group) {
            asort($group);
        }

        foreach ($groups as $section => $children) {
            foreach ($children as $key => $child) {
                $groups[$section][$key] = $particles[$key];
            }
        }

        $params = [
            'particles' => $groups
        ];

        $params = [
            'content' => $this->container['admin.theme']->render('@gantry-admin/modals/particle-picker.html.twig', $params)
        ];

        return $this->container['admin.theme']->render('@gantry-admin/partials/layout.html.twig', $params);
    }


    protected function getParticles()
    {
        $particles = $this->container['particles']->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            $particleName = isset($particle['name']) ? $particle['name'] : $name;
            $list[$type][$name] = $particleName;
        }

        return $list;
    }
}
