<?php
namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;

class Assignments
{
    use GantryTrait;

    protected $style_id;

    public function __construct($style_id)
    {
        $this->style_id = $style_id;
    }

    public function get()
    {
        return $this->getMenu();
    }

    public function set($data)
    {
        if (isset($data['menu'])) {
            $this->setMenu($data['menu']);
        }
    }

    public function types()
    {
        return ['menu'];
    }

    public function getMenu()
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
        $data = \MenusHelper::getMenuLinks();

        $userid = \JFactory::getUser()->id;

        $list = [];

        foreach ($data as $menu) {
            $items = [];
            foreach ($menu->links as $link) {
                $items[] = [
                    'name' => 'menu[' . $link->value . ']',
                    'field' => ['id', 'link' . $link->value],
                    'value' => $link->template_style_id == $this->style_id,
                    'disabled' => $link->type != 'component' || $link->checked_out && $link->checked_out != $userid,
                    'label' => str_repeat('- ', $link->level-1) . $link->text
                ];
            }
            $group = [
                'label' => $menu->title ?: $menu->menutype,
                'items' => $items
            ];

            $list[] = $group;
        }

        return $list;
    }

    public function setMenu($data)
    {
        $active = array_keys(array_filter($data, function($value) {return $value == 1; }));

        // Detect disabled template.
        $extension = \JTable::getInstance('Extension');

        $template = static::gantry()['theme.name'];
        if ($extension->load(array('enabled' => 0, 'type' => 'template', 'element' => $template, 'client_id' => 0))) {
            throw new \RuntimeException(\JText::_('COM_TEMPLATES_ERROR_SAVE_DISABLED_TEMPLATE'));
        }

        $style = \JTable::getInstance('Style', 'TemplatesTable');
        if (!$style->load($this->style_id) || $style->client_id != 0) {
            throw new \RuntimeException('Template style does not exist');
        }

        $user = \JFactory::getUser();
        $n = 0;

        if ($user->authorise('core.edit', 'com_menus')) {
            $db   = \JFactory::getDbo();
            $user = \JFactory::getUser();

            if (!empty($active) && is_array($active)) {
                \JArrayHelper::toInteger($active);

                // Update the mapping for menu items that this style IS assigned to.
                $query = $db->getQuery(true)
                    ->update('#__menu')
                    ->set('template_style_id = ' . (int) $style->id)
                    ->where('id IN (' . implode(',', $active) . ')')
                    ->where('template_style_id != ' . (int) $style->id)
                    ->where('checked_out IN (0,' . (int) $user->id . ')');
                $db->setQuery($query);
                $db->execute();
                $n += $db->getAffectedRows();
            }

            // Remove style mappings for menu items this style is NOT assigned to.
            // If unassigned then all existing maps will be removed.
            $query = $db->getQuery(true)
                ->update('#__menu')
                ->set('template_style_id = 0');

            if (!empty($active)) {
                $query->where('id NOT IN (' . implode(',', $active) . ')');
            }

            $query->where('template_style_id = ' . (int) $style->id)
                ->where('checked_out IN (0,' . (int) $user->id . ')');
            $db->setQuery($query);
            $db->execute();

            $n += $db->getAffectedRows();
        }

        // Clean the cache.
        $this->cleanCache();

        return ($n > 0);
    }

    protected function cleanCache()
    {
        $this->cleanCacheType('com_templates');
        $this->cleanCacheType('_system');
    }

    private function cleanCacheType($group)
    {
        $conf = \JFactory::getConfig();
        $dispatcher = \JEventDispatcher::getInstance();

        $options = array(
            'defaultgroup' => $group,
            'cachebase' => $conf->get('cache_path', JPATH_SITE . '/cache')
        );

        $cache = \JCache::getInstance('callback', $options);
        $cache->clean();

        // Trigger the onContentCleanCache event.
        $dispatcher->trigger('onContentCleanCache', $options);
    }
}
