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

    public function __construct(Container $container)
    {
        //Make sure that cache folder exists, otherwise it will be removed from the lookup.
        $cachePath = $this->getCachePath();
        Folder::create(GANTRY5_ROOT . '/' . $cachePath);

        $this->items = [
            'streams' => [
                'gantry-cache' => [
                    'type' => 'Stream',
                    'prefixes' => [
                        '' => [$cachePath]
                    ]
                ],
                'gantry-themes' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemesPaths()
                ],
                'gantry-theme' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getThemePaths()
                ],
                'gantry-media' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => $this->getMediaPaths()
                ],
                'gantry-engine' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-theme://engine']
                    ]
                ],
                'gantry-particles' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-engine://particles']
                    ]
                ],
                'gantry-blocks' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-engine://blocks']
                    ]
                ],
                'gantry-admin' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => []
                ],
                'gantry-blueprints' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-engine://blueprints', 'gantry-theme://blueprints'],
                        'particles/' => ['gantry-particles://'],
                        'styles/' => ['gantry-styles://']
                    ]
                ],
                'gantry-config' => [
                    'type' => 'ReadOnlyStream',
                    'prefixes' => [
                        '' => ['gantry-theme://config', 'gantry-engine://config']
                    ]
                ]
            ]
        ];
    }

    abstract public function getCachePath();
    abstract public function getThemesPaths();
    abstract public function getMediaPaths();

    public function getThemePaths()
    {
        return ['' => []];
    }
}
