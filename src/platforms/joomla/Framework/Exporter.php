<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Joomla\Module\ModuleFinder;
use Gantry\Joomla\StyleHelper;

class Exporter
{
    public function all()
    {
        return [
            'outlines' => $this->outlines(),
            'positions' => $this->positions()
        ];
    }

    public function positions()
    {
        $finder = new ModuleFinder();
        $modules = $finder->particle()->find();

        return $modules->export();
    }

    public function outlines()
    {
        $gantry = Gantry::instance();
        $styles = StyleHelper::loadStyles($gantry['theme.name']);

        $list = [
            'default' => ['title' => 'Default'],
            '_error' => ['title' => 'Error'],
            '_offline' => ['title' => 'Offline'],
            '_body_only' => ['title' => 'Body Only'],
        ];
        $inheritance = [];

        foreach ($styles as $style) {
            $name = $base = strtolower(trim(preg_replace('|[^a-z\d_-]+|ui', '_', $style->title), '_'));
            $i = 0;
            while (isset($list[$name])) {
                $i++;
                $name = "{$base}-{$i}";
            };
            $inheritance[$style->id] = $name;
            $list[$name] = [
                'id' => (int) $style->id,
                'title' => $style->title,
                'home' => $style->home,
            ];
            if (!$style->home) {
                unset($list[$name]['home']);
            }
        }

        foreach ($list as $name => &$style) {
            $id = isset($style['id']) ? $style['id'] : $name;
            $config = ConfigServiceProvider::load($gantry, $id, false, false);

            // Update layout inheritance.
            $layout = Layout::instance($id);
            $layout->name = $name;
            foreach ($inheritance as $from => $to) {
                $layout->updateInheritance($from, $to);
            }
            $style['preset'] = $layout->preset['name'];
            $config['index'] = $layout->buildIndex();
            $config['layout'] = $layout->export();

            // Update atom inheritance.
            $atoms = $config->get('page.head.atoms');
            if (is_array($atoms)) {
                $atoms = new Atoms($atoms);
                foreach ($inheritance as $from => $to) {
                    $atoms->updateInheritance($from, $to);
                }
                $config->set('page.head.atoms', $atoms->update()->toArray());
            }

            $style['config'] = $config->toArray();
        }

        return $list;
    }
}
