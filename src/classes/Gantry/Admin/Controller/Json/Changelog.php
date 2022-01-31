<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Admin\JsonController;
use Gantry\Component\Remote\Response as RemoteResponse;
use Gantry\Component\Response\JsonResponse;

/**
 * Class Changelog
 * @package Gantry\Admin\Controller\Json
 */
class Changelog extends JsonController
{
    /** @var string */
    protected $url = 'https://raw.githubusercontent.com/gantry/gantry5';
    /** @var string */
    protected $fullurl = 'https://github.com/gantry/gantry5/blob/develop';
    /** @var string */
    protected $issues = 'https://github.com/gantry/gantry5/issues';
    /** @var string */
    protected $contrib = 'https://github.com';
    /** @var string */
    protected $file = 'CHANGELOG.md';
    /** @var array */
    protected $platforms = ['common' => 'share-alt', 'joomla' => '', 'wordpress' => '', 'grav' => ''];
    /** @var array */
    protected $httpVerbs = [
        'POST' => [
            '/' => 'index'
        ]
    ];

    /**
     * @return JsonResponse
     */
    public function index()
    {
        $version = $this->request->post['version'];
        $lookup = $version;

        if ($version === '@version@') {
            $version = 'develop';
            $lookup  = '';
        }

        if (strpos($version, 'dev-') === 0) {
            $version = preg_replace('/^dev-/i', '', $version);
            $lookup  = '';
        }

        $url       = $this->url . '/' . $version . '/' . $this->file;
        $changelog = RemoteResponse::get($url);

        if ($changelog) {
            $found = preg_match("/(#\\s" . $lookup . ".+?\\n.*?)(?=\\n{1,}#|$)/uis", $changelog, $changelog);

            if ($found) {
                $changelog = \Parsedown::instance()->parse($changelog[0]);

                // auto-link issues
                $changelog = preg_replace("/#(\\d{1,})/ui", '<a target="_blank" rel="noopener" href="' . $this->issues . '/$1">#$1</a>', $changelog);

                // auto-link contributors
                $changelog = preg_replace("/@([\\w]+)[^\\w]/ui", '<a target="_blank" rel="noopener" href="' . $this->contrib . '/$1">@$1</a> ', $changelog);

                // add icons for platforms
                foreach($this->platforms as $platform => $icon) {
                    $changelog = preg_replace('/(<a href="\#' . $platform . '">)/uis', '$1<i class="fa fa-' . ($icon ?: $platform) . '" aria-hidden="true"></i> ', $changelog);
                }
            } else {
                $changelog = 'No changelog for version <strong>' . $version . '</strong> was found.';
            }
        }

        $response = [
            'html' => $this->render('@gantry-admin/ajax/changelog.html.twig', [
                'changelog' => $changelog,
                'version'   => $version,
                'url'       => $url,
                'fullurl'   => $this->fullurl . '/' . $this->file
            ])
        ];

        return new JsonResponse($response);
    }
}
