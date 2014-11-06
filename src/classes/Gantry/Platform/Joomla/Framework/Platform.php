<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config as Config;
use Gantry\Component\Filesystem\Folder;

/**
 * The Platform Configuration class contains configuration information.
 *
 * @author RocketTheme
 * @license MIT
 */
class Platform extends Config
{
    protected $items;

    public function __construct()
    {
        $cache = Folder::getRelativePath(JPATH_CACHE);
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
                    ]
                ],
                'engine' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [

                    ]
                ],
                'widgets' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['engine://widgets', 'theme://twig/widgets']
                    ]
                ],
                'admin' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                    ]
                ],
                'blueprints' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['engine://blueprints', 'theme://blueprints'],
                        'widgets/' => ['widgets://']
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
