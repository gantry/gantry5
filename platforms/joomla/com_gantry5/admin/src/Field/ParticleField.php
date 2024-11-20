<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Component\Gantry5\Administrator\Field;

use Gantry\Admin\Router;
use Gantry\Admin\Theme;
use Gantry\Framework\Gantry;
use Gantry\Joomla\StyleHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class JFormFieldParticle
 */
class ParticleField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Particle';

    /**
     * {@inheritDoc}
     */
    protected function getInput()
    {
        /** @var \Joomla\CMS\Application\AdministratorApplication $app */
        $app = Factory::getApplication();

        if (!defined('GANTRYADMIN_PATH')) {
            define('GANTRYADMIN_PATH', JPATH_ADMINISTRATOR . '/components/com_gantry5');
        }

        try {
            $gantry = Gantry::instance();

            $gantry['router'] = function ($c): Router {
                return new Router($c);
            };
        } catch (\Exception $e) {
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
        } elseif (!\file_exists(JPATH_SITE . "/templates/{$style->template}/gantry/theme.yaml")) {
            $app->enqueueMessage(
                Text::sprintf('GANTRY5_PARTICLE_FIELD_NO_GANTRY5_STYLE', $style->title),
                'warning'
            );
        }

        /** @var Router $router */
        $router = $gantry['router'];
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
            'value' => \json_decode($this->value, true)
        ];

        /** @var Theme $adminTheme */
        $adminTheme = $gantry['admin.theme'];

        $params = [
            'content' => $adminTheme->render('@gantry-admin/forms/fields/gantry/particle.html.twig', $field)
        ];

        return $adminTheme->render('@gantry-admin/partials/fieldbase.html.twig', $params);
    }
}
