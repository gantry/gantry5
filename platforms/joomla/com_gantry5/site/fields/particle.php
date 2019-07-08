<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Gantry\Admin\Router;
use Gantry\Admin\Theme;
use Gantry\Framework\Gantry;
use Gantry\Joomla\JoomlaFactory;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormField;

/**
 * Class JFormFieldParticle
 * FIXME: Joomla 4: Class name?
 */
class JFormFieldParticle extends FormField
{
    protected $type = 'Particle';
    protected $container;

    /**
     * @return string
     * @throws Exception
     */
    protected function getInput()
    {
        $app = JoomlaFactory::getApplication();

        // Detect Gantry Framework or fail gracefully.
        if (!class_exists('Gantry5\Loader')) {
            $app->enqueueMessage(
                Text::sprintf('MOD_GANTRY5_PLUGIN_MISSING', Text::_('MOD_GANTRY5_PARTICLE')),
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

            $language = JoomlaFactory::getLanguage();
            $language->load('com_gantry5', JPATH_ADMINISTRATOR)
                || $language->load('com_gantry5', GANTRYADMIN_PATH);

            $this->container = Gantry::instance();
            $this->container['router'] = function ($c) {
                return new Router($c);
            };

        } catch (Exception $e) {
            $app->enqueueMessage(
                Text::sprintf($e->getMessage()),
                'error'
            );
            return '';
        }

        // TODO: Use better style detection.
        $style = StyleHelper::getDefaultStyle();

        if (!$style->template) {
            $app->enqueueMessage(
                Text::_('GANTRY5_PARTICLE_FIELD_NO_DEFAULT_STYLE'),
                'warning'
            );
        } elseif (!file_exists(JPATH_SITE . "/templates/{$style->template}/gantry/theme.yaml")) {
            $app->enqueueMessage(
                Text::sprintf('GANTRY5_PARTICLE_FIELD_NO_GANTRY5_STYLE', $style->title),
                'warning'
            );
        }

        /** @var Router $router */
        $router = $this->container['router'];
        $router->setTheme($style->template, null)->load();

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

        /** @var Theme $adminTheme */
        $adminTheme = $this->container['admin.theme'];

        $params = [
            'content' => $adminTheme->render('@gantry-admin/forms/fields/gantry/particle.html.twig', $field)
        ];

        return $adminTheme->render('@gantry-admin/partials/layout.html.twig', $params);
    }
}
