<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Plugin\System\Gantry5\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class WarningField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'Warning';

    /**
     * {@inheritDoc}
     */
    protected function getLabel()
    {
        return 'Gantry 5';
    }

    /**
     * {@inheritDoc}
     */
    protected function getInput()
    {
        $app   = Factory::getApplication();
        $input = $app->getInput();

        $route = '';
        $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');

        if ($cid) {
            $styles = $this->getStyles();
            $selected = array_intersect_key($styles, array_flip($cid));
            if ($selected) {
                $theme = reset($selected);
                $id = key($selected);
                $token = Factory::getApplication()->getFormToken();
                $route = "index.php?option=com_gantry5&view=configurations/{$id}";
            }
        }

        if (!$route) {
            return '<a href="index.php?option=com_gantry5" class="btn" style="background:#439a86; color:#fff;">Gantry 5</a>';
        }

        $lang = Factory::getApplication()->getLanguage();
        $lang->load('com_gantry5', JPATH_ADMINISTRATOR) || $lang->load('com_gantry5', JPATH_ADMINISTRATOR . '/components/com_gantry5');

        $title1 = Text::_('GANTRY5_PLATFORM_LAYOUT');
        $title2 = Text::_('GANTRY5_PLATFORM_STYLES');
        $title3 = Text::_('GANTRY5_PLATFORM_PAGESETTINGS');

        return <<<HTML
<a href="{$route}/layout&theme={$theme}&{$token}=1" class="btn" style="background:#439a86; color:#fff;">{$title1}</a>
<a href="{$route}/styles&theme={$theme}&{$token}=1" class="btn" style="background:#439a86; color:#fff;">{$title2}</a>
<a href="{$route}/page&theme={$theme}&{$token}=1" class="btn" style="background:#439a86; color:#fff;">{$title3}</a>
HTML;
    }

    /**
     * @return array
     */
    private function getStyles(): array
    {
        static $list;

        if ($list === null) {
            $db    = $this->getDatabase();
            $query = $db->createQuery();

            $query->select('s.id, s.template')
                ->from('#__template_styles as s')
                ->where('s.client_id = 0')
                ->where('e.enabled = 1')
                ->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

            $db->setQuery($query);
            $templates = (array)$db->loadObjectList();

            $list = [];

            foreach ($templates as $template) {
                if ($this->isGantryTemplate($template->template)) {
                    $list[$template->id] = $template->template;
                }
            }
        }

        return $list;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isGantryTemplate($name): bool
    {
        return \file_exists(JPATH_SITE . "/templates/{$name}/gantry/theme.yaml");
    }
}
