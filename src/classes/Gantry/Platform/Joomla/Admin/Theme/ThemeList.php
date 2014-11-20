<?php
namespace Gantry\Admin\Theme;

class ThemeList
{
    /**
     * @return array
     */
    public static function getStyles()
    {
        // Load styles
        $db    = \JFactory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('s.id, s.template AS name, title, s.params')
            ->from('#__template_styles AS s')
            ->where('s.client_id = 0')
            ->where('e.enabled = 1')
            ->leftJoin('#__extensions AS e ON e.element=s.template AND e.type='
            . $db->quote('template') . ' AND e.client_id=s.client_id');

        $db->setQuery($query);
        $templates = (array) $db->loadObjectList();

        $list = array();

        foreach ($templates as $template)
        {
            if (file_exists(JPATH_SITE . '/templates/' . $template->name . '/includes/gantry.php'))
            {
                $params = new \JRegistry;
                $params->loadString($template->params);

                $template->thumbnail = 'template_thumbnail.png';
                $template->params = $params->toArray();

                $list[$template->id] = $template;
            }
        }

        return $list;
    }
}
