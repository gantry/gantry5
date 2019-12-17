<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

class Page extends Base\Page
{
    public $home;
    public $outline;
    public $language;
    public $direction;

    // Joomla specific properties.
    public $theme;
    public $baseUrl;
    public $title;
    public $description;

    public function __construct($container)
    {
        parent::__construct($container);

        $app = \JFactory::getApplication();
        $document = \JFactory::getDocument();
        $input = $app->input;

        $this->tmpl     = $input->getCmd('tmpl', '');
        $this->option   = $input->getCmd('option', '');
        $this->view     = $input->getCmd('view', '');
        $this->layout   = $input->getCmd('layout', '');
        $this->task     = $input->getCmd('task', '');
        $this->itemid   = $input->getInt('Itemid', 0);
        $this->printing = $input->getCmd('print', '');

        $this->class = '';
        if ($this->itemid) {
            $menuItem = $app->getMenu()->getActive();
            if ($menuItem && $menuItem->id) {
                $this->home = (bool) $menuItem->home;
                $this->class = $menuItem->params->get('pageclass_sfx', '');
            }
        }
        $templateParams = $app->getTemplate(true);
        $this->outline = Gantry::instance()['configuration'];
        $this->sitename = $app->get('sitename');
        $this->theme = $templateParams->template;
        $this->baseUrl = \JUri::base(true);
        $this->title = $document->title;
        $this->description = $document->description;

        // Document has lower case language code, which causes issues with some JS scripts (Snipcart). Use tag instead.
        $code = explode('-', $document->getLanguage(), 2);
        $language =  array_shift($code);
        $country = strtoupper(array_shift($code));
        $this->language = $language . ($country ? '-' . $country : '');
        $this->direction = $document->direction;
    }

    public function url(array $args = [])
    {
        $url = \JUri::getInstance();

        foreach ($args as $key => $val) {
            $url->setVar($key, $val);
        }

        return $url->toString();
    }

    public function htmlAttributes()
    {
        $attributes = [
                'lang' => $this->language,
                'dir' => $this->direction
            ]
            + (array) $this->config->get('page.html', []);

        return $this->getAttributes($attributes);
    }

    public function bodyAttributes($attributes = [])
    {
        if ($this->tmpl == 'component') {
            $classes = ['contentpane', 'modal'];
        } else {
            $classes = ['site', $this->option, "view-{$this->view}"];
            $classes[] = $this->layout ? 'layout-' . $this->layout : 'no-layout';
            $classes[] = $this->task ? 'task-' . $this->task : 'no-task';
        }
        $classes[] = 'dir-' . $this->direction;
        if ($this->class) $classes[] = $this->class;
        if ($this->printing) $classes[] = 'print-mode';
        if ($this->itemid) $classes[] = 'itemid-' . $this->itemid;
        if ($this->outline) $classes[] = 'outline-' . $this->outline;

        $baseAttributes = (array) $this->config->get('page.body.attribs', []);
        if (!empty($baseAttributes['class'])) {
            $baseAttributes['class'] = array_merge((array) $baseAttributes['class'], $classes);
        } else {
            $baseAttributes['class'] = $classes;
        }

        return $this->getAttributes($baseAttributes, $attributes);
    }
}
