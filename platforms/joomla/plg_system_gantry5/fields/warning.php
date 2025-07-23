<?php

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

// Use namespaced class for Joomla 5 compatibility
class WarningField extends FormField
{
    protected $type = 'Warning';

    protected function getLabel()
    {
        return 'Gantry 5';
    }

    protected function getInput()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $input = $app->input;

        $route = '';
        $cid = $input->post->get('cid', (array) $input->getInt('id'), 'array');
        if ($cid) {
            $styles = $this->getStyles();
            $selected = array_intersect_key($styles, array_flip($cid));
            if ($selected) {
                $theme = reset($selected);
                $id = key($selected);
                $token = Session::getFormToken();
                $route = "index.php?option=com_gantry5&view=configurations/{$id}";
            }
        }

        if (!$route) {
            return '<a href="index.php?option=com_gantry5" class="btn" style="background:#439a86; color:#fff;">Gantry 5</a>';
        }

        /** @var Language $lang */
        $lang = Factory::getLanguage();
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
    private function getStyles()
    {
        static $list;

        if ($list === null) {
            // Load styles
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db
                ->getQuery(true)
                ->select('s.id, s.template')
                ->from('#__template_styles as s')
                ->where('s.client_id = 0')
                ->where('e.enabled = 1')
                ->leftJoin('#__extensions as e ON e.element=s.template AND e.type=' . $db->quote('template') . ' AND e.client_id=s.client_id');

            $db->setQuery($query);
            $templates = (array)$db->loadObjectList();

            $list = array();

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
    private function isGantryTemplate($name)
    {
        return file_exists(JPATH_SITE . "/templates/{$name}/gantry/theme.yaml");
    }
}

// Compatibility alias for Joomla 4
if (!class_exists('JFormFieldWarning') && version_compare(JVERSION, '5.0', '<')) {
    class_alias('WarningField', 'JFormFieldWarning');
}
