<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Pages implements \ArrayAccess, \Iterator
{
    use ArrayAccessWithGetters, Iterator, Export;

    /**
     * @var array
     */
    protected $items;

    public function __construct()
    {
        $folder = PRIME_ROOT . '/pages';
        if (!is_dir($folder)) {
            throw new \RuntimeException('Prime has been not set up (pages missing)', 500);
        }

        $options = [
            'pattern' => '|\.html\.twig|',
            'filters' => ['key' => '|\.html\.twig|', 'value' => function () { return []; }],
            'key' => 'SubPathname'
        ];

        $this->items = Folder::all($folder, $options);
        ksort($this->items);
    }
}
