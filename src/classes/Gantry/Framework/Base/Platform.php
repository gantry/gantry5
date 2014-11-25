<?php
namespace Gantry\Framework\Base;

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

abstract class Platform
{
    use NestedArrayAccess, Export;

    protected $items;

    protected $cachePath;
    protected $themePath;

    public function __construct(Container $container)
    {
        //Make sure that cache folder exists, otherwise it will be removed from the lookup.
        $cachePath = $this->getCachePath();
        Folder::create(GANTRY5_ROOT . '/' . $cachePath);

        $this->items = [
            'streams' => [
                'cache' => [
                    'type' => 'Stream',
                    'prefixes' => [
                        '' => [$cachePath]
                    ]
                ],
                'themes' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemesPaths()
                ],
                'theme' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemePaths()
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

    abstract public function getCachePath();
    abstract public function getThemesPaths();

    public function getThemePaths()
    {
        return ['' => []];
    }
}
