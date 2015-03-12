<?php
namespace Gantry\Framework;

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
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];
        /** @var UniformResourceIterator $iterator */

        $iterator = $locator->getIterator(
            $path,
            UniformResourceIterator::CURRENT_AS_PATHNAME | UniformResourceIterator::KEY_AS_FILENAME |
            UniformResourceIterator::UNIX_PATHS | UniformResourceIterator::SKIP_DOTS
        );

        $files = [];
        foreach ($iterator as $name => $path) {
            $files[$name] = $name;
        }

        if (!isset($files['default'])) {
            throw new \RuntimeException('Fatal error: Theme does not have default layout');
        }

        unset($files['default']);

        $layouts = array_keys($files);
        sort($layouts);
        array_unshift($layouts, 'default');

        $this->items = $layouts;

        return $this;
    }
}
