<?php
namespace Gantry\Framework;

use Gantry\Admin\Theme\ThemeList;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\Configuration\AbstractConfigurationCollection;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceIterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Configurations extends AbstractConfigurationCollection
{
    /**
     * @param string $path
     * @return $this
     */
    public function load($path = 'gantry-config://')
    {
        $gantry = Gantry::instance();

        $styles = ThemeList::getStyles($gantry['theme.name']);

        $layouts = [];
        foreach ($styles as $style) {
            $layouts[$style->id] = $style->title;
        }

        asort($layouts);

        $this->items = ['default' => 'Default'] + $layouts;

        return $this;
    }
}
