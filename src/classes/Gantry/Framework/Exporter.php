<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

/**
 * @package Gantry\Framework
 */
class Exporter
{
    protected $files = [];

    /**
     * @return array
     */
    public function all()
    {
        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];
        $details = $theme->details();

        return [
            'export' => [
                'gantry' => [
                    'version' => GANTRY5_VERSION !== '@version@' ? GANTRY5_VERSION : 'GIT',
                    'format' => 1
                ],
                'platform' => [
                    'name' => 'default',
                    'version' => '0.0'
                ],
                'theme' => [
                    'name' => $details->get('name'),
                    'title' => $details->get('details.name'),
                    'version' => $details->get('details.version'),
                    'date' => $details->get('details.date'),
                    'author' => $details->get('details.author'),
                    'copyright' => $details->get('details.copyright'),
                    'license' => $details->get('details.license'),
                ]
            ],
            'outlines' => $this->outlines(),
            'positions' => $this->positions(),
            'menus' => $this->menus(),
            'content' => $this->articles(),
            'categories' => $this->categories(),
            'files' => $this->files,
        ];
    }

    /**
     * @return array
     */
    public function outlines()
    {
        // TODO: implement
        return [];
    }

    /**
     * @param bool $all
     * @return array
     */
    public function positions($all = true)
    {
        // TODO: implement
        return [];
    }

    /**
     * @return array
     */
    public function menus()
    {
        // TODO: implement
        return [];
    }

    /**
     * @return array
     */
    public function articles()
    {
        // TODO: implement
        return [];
    }

    /**
     * @return array
     */
    public function categories()
    {
        // TODO: implement
        return [];
    }


    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function getOutlineAssignments($configuration)
    {
        // TODO: implement
        return [];
    }
}
