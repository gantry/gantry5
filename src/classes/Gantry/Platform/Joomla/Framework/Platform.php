<?php
namespace Gantry\Framework;

use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\NestedArrayAccess;
use RocketTheme\Toolbox\DI\Container;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */

class Platform
{
    use NestedArrayAccess, Export;

    protected $items;

    public function __construct(Container $container)
    {
        $cache = Folder::getRelativePath(JPATH_CACHE) . '/gantry';

        //Make sure that cache folder exists, otherwise it will be removed from the lookup.
        Folder::create($cache);

        $this->items = [
            'streams' => [
                'cache' => [
                    'type' => 'Stream',
                    'prefixes' => [
                        '' => [$cache]
                    ]
                ],
                'themes' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['templates']
                    ]
                ],
                'theme' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => [] // Always define
                    ]
                ],
                'engine' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['theme://engine']
                    ]
                ],
                'particles' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['engine://particles']
                    ]
                ],
                'gantry-admin' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => []
                ],
                'blueprints' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['engine://blueprints', 'theme://blueprints'],
                        'particles/' => ['particles://']
                    ]
                ],
                'config' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['engine://config', 'theme://config']
                    ]
                ]
            ]
        ];
    }
}
