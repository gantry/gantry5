<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Remote\Response as RemoteResponse;
use Gantry\Component\Response\JsonResponse;
use Symfony\Component\Yaml\Yaml as YamlParser;

class Changelog extends JsonController
{
    protected $url = 'https://raw.githubusercontent.com/gantry/gantry5';
    protected $fullurl = 'https://github.com/gantry/gantry5/blob/develop/CHANGELOG.md';
    protected $issues = 'https://github.com/gantry/gantry5/issues/';
    protected $contrib = 'https://github.com/';
    protected $file = 'CHANGELOG.md';
    protected $httpVerbs = [
        'POST' => [
            '/' => 'index'
        ]
    ];

    public function index()
    {
        $version = $this->request->post['version'];
        if ($version == '@version@') {
            $version = 'develop';
        }

        $changelog = RemoteResponse::get($this->url . '/' . $version . '/' . $this->file);

        if ($changelog) {
            $found = preg_match("/(#\\s" . ($version == 'develop' ? '' : $version) . ".+?\\n.*?)(?=\\n{1,}#|$)/uis", $changelog, $changelog);

            if ($found) {
                $changelog = \Parsedown::instance()->parse($changelog[0]);

                // fix issues links
                $changelog = preg_replace("/#(\\d{1,})/uis", '<a target="_blank" href="' . $this->issues . '$1">#$1</a>', $changelog);

                // fix contributors links
                $changelog = preg_replace("/@([\\w]+)[^\\w]/uis", '<a target="_blank" href="' . $this->contrib . '$1">@$1</a> ', $changelog);
            } else {
                $changelog = 'No changelog for version <strong>' . $version . '</strong> was found.';
            }
        }

        $response = [
            'html' => $this->container['admin.theme']->render('@gantry-admin/ajax/changelog.html.twig', [
                'changelog'     => $changelog,
                'version'       => $version,
                'fullchangelog' => $this->fullurl
            ])
        ];

        return new JsonResponse($response);
    }
}
