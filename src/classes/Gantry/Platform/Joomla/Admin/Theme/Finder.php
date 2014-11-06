<?php
namespace Gantry\Admin\Theme;

class Finder
{
    public function all()
    {

    }

    /**
     * @return array
     */
    private function getStyles()
    {
        // Load styles
        $db    = \JFactory::getDbo();
        $query = $db
            ->getQuery(true)
            ->select('s.id, e.extension_id, s.template, s.home, s.title, e.enabled, s.params')
            ->from('#__template_styles as s')
            ->leftJoin("#__extensions as e ON e.type='template' AND e.client_id=s.client_id")
            ->where('s.client_id=0');

        $db->setQuery($query);
        $templates = (array) $db->loadObjectList();

        $list = array();

        foreach ($templates as $template)
        {
            if (file_exists(JPATH_SITE . '/templates/' . $template->template . '/includes/gantry.php'))
            {
                $params = new \JRegistry;
                $params->loadString($template->params);

                $list[$template->id] = ($params->get('master') == 'true');
            }
        }


        return $list;
    }
}
